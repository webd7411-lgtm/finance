<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StationSetting;
use App\Models\Tank;
use App\Models\TankDipChart;
use App\Models\TankDipReading;
use App\Models\TankStockAdjustment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TankReconciliationService
{
    /**
     * Look up or linearly interpolate tank volume (Liters) from its calibration chart for a given dip height.
     */
    public function interpolateLitersFromChart(Tank $tank, float $dipMm): array
    {
        $points = TankDipChart::where('tank_id', $tank->id)
            ->orderBy('dip_mm', 'asc')
            ->get(['dip_mm', 'liters']);

        if ($points->isEmpty()) {
            return [
                'has_chart'     => false,
                'liters'        => null,
                'matched_exact' => false,
                'out_of_range'  => false,
                'min_mm'        => null,
                'max_mm'        => null,
            ];
        }

        $minMm = (float) $points->first()->dip_mm;
        $maxMm = (float) $points->last()->dip_mm;

        if ($dipMm < $minMm - 0.0001 || $dipMm > $maxMm + 0.0001) {
            return [
                'has_chart'     => true,
                'liters'        => null,
                'matched_exact' => false,
                'out_of_range'  => true,
                'min_mm'        => $minMm,
                'max_mm'        => $maxMm,
            ];
        }

        $prev = null;
        foreach ($points as $pt) {
            $pMm = (float) $pt->dip_mm;
            $pLiters = (float) $pt->liters;

            if (abs($pMm - $dipMm) < 0.0001) {
                return [
                    'has_chart'     => true,
                    'liters'        => round($pLiters, 2),
                    'matched_exact' => true,
                    'out_of_range'  => false,
                    'min_mm'        => $minMm,
                    'max_mm'        => $maxMm,
                ];
            }

            if ($prev !== null && $dipMm > (float) $prev->dip_mm && $dipMm < $pMm) {
                $m1 = (float) $prev->dip_mm;
                $l1 = (float) $prev->liters;
                $ratio = ($dipMm - $m1) / ($pMm - $m1);
                $interpolated = $l1 + ($ratio * ($pLiters - $l1));

                return [
                    'has_chart'     => true,
                    'liters'        => round($interpolated, 2),
                    'matched_exact' => false,
                    'out_of_range'  => false,
                    'min_mm'        => $minMm,
                    'max_mm'        => $maxMm,
                ];
            }

            $prev = $pt;
        }

        return [
            'has_chart'     => true,
            'liters'        => null,
            'matched_exact' => false,
            'out_of_range'  => true,
            'min_mm'        => $minMm,
            'max_mm'        => $maxMm,
        ];
    }

    /**
     * Compute effective warning and critical tolerance limits in Liters for a tank.
     */
    public function calculateToleranceThresholds(Tank $tank, float $bookStock, ?array $stationConfig = null): array
    {
        $cfg = $tank->getEffectiveToleranceSettings($stationConfig);
        $mode = $cfg['tolerance_mode'];

        $wPct = ($bookStock * $cfg['warning_percent']) / 100.0;
        $cPct = ($bookStock * $cfg['critical_percent']) / 100.0;
        $wLit = $cfg['warning_liters'];
        $cLit = $cfg['critical_liters'];

        [$warning, $critical] = match ($mode) {
            'fixed_liters' => [$wLit, $cLit],
            'percentage'   => [$wPct, $cPct],
            'lower_of'     => [min($wLit, $wPct), min($cLit, $cPct)],
            default        => [max($wLit, $wPct), max($cLit, $cPct)],
        };

        $warning = max(0.0, round($warning, 2));
        $critical = max($warning, round($critical, 2));

        return [
            'mode'            => $mode,
            'warning_liters'  => $warning,
            'critical_liters' => $critical,
            'source'          => $cfg['source'],
            'raw'             => $cfg,
        ];
    }

    /**
     * Evaluate a dip variance against the tank's active tolerance thresholds.
     */
    public function evaluateVariance(Tank $tank, float $varianceLiters, float $bookStock, ?array $stationConfig = null): array
    {
        $thresholds = $this->calculateToleranceThresholds($tank, $bookStock, $stationConfig);
        $abs = abs($varianceLiters);

        if ($abs < 0.01) {
            $status = 'balanced';
        } elseif ($abs <= $thresholds['warning_liters']) {
            $status = 'within_tolerance';
        } elseif ($abs <= $thresholds['critical_liters']) {
            $status = 'warning';
        } else {
            $status = 'critical';
        }

        return array_merge($thresholds, [
            'status' => $status,
        ]);
    }

    /**
     * Detect consecutive unreconciled negative variance readings indicating persistent loss/leak/calibration drift.
     */
    public function detectConsecutiveShortageTrend(Tank $tank, ?array $stationConfig = null): array
    {
        $config = $stationConfig ?? StationSetting::getTankConfig();
        $threshold = (int) ($config['consecutive_shortage_alert'] ?? 3);

        if ($threshold <= 0) {
            return ['has_trend' => false, 'streak' => 0, 'threshold' => 0];
        }

        $readings = TankDipReading::where('tank_id', $tank->id)
            ->whereNotIn('reading_context', ['pre_delivery', 'initial'])
            ->latest('reading_date')
            ->latest('id')
            ->take(max($threshold + 5, 10))
            ->get();

        $streak = 0;
        foreach ($readings as $reading) {
            if ($reading->is_reconciled) {
                break;
            }
            if ((float) $reading->variance_liters < -0.01) {
                $streak++;
            } else {
                break;
            }
        }

        return [
            'has_trend' => $streak >= $threshold,
            'streak'    => $streak,
            'threshold' => $threshold,
        ];
    }

    /**
     * Record a physical dip measurement, compute cumulative & period variances,
     * evaluate tolerance thresholds, and optionally auto-reconcile if configured.
     */
    public function recordDip(Tank $tank, array $data, int $userId): TankDipReading
    {
        return DB::transaction(function () use ($tank, $data, $userId) {
            $tank = Tank::lockForUpdate()->with('product')->findOrFail($tank->id);
            $config = StationSetting::getTankConfig();

            $dipHeight = (float) $data['dip_cm'];
            $measured = isset($data['measured_liters']) && $data['measured_liters'] !== '' && $data['measured_liters'] !== null
                ? (float) $data['measured_liters']
                : null;
            $isChartCalculated = (bool) ($data['is_chart_calculated'] ?? false);

            if ($measured === null) {
                $lookup = $this->interpolateLitersFromChart($tank, $dipHeight);
                if ($lookup['liters'] !== null) {
                    $measured = (float) $lookup['liters'];
                    $isChartCalculated = true;
                } else {
                    $msg = $lookup['has_chart'] && $lookup['out_of_range']
                        ? "Dip height {$dipHeight} is outside this tank's calibration chart range ({$lookup['min_mm']} - {$lookup['max_mm']}). Please enter Measured Volume manually."
                        : 'Measured Volume (Liters) is required when no calibration chart is configured for this tank.';
                    throw ValidationException::withMessages(['measured_liters' => $msg]);
                }
            }

            $bookStock = (float) $tank->current_stock;
            $variance = round($measured - $bookStock, 2);
            $context = $data['reading_context'] ?? 'daily_routine';

            $previousDip = TankDipReading::where('tank_id', $tank->id)
                ->latest('reading_date')
                ->latest('id')
                ->first();

            if ($previousDip && !$previousDip->is_reconciled && $context !== 'pre_delivery') {
                $periodVariance = round($variance - (float) $previousDip->variance_liters, 2);
            } else {
                $periodVariance = $variance;
            }

            $eval = $this->evaluateVariance($tank, $variance, $bookStock, $config);
            $unitCost = (float) ($tank->product?->purchase_price ?? 0);
            $financialImpact = round($variance * $unitCost, 2);

            $dip = TankDipReading::create([
                'tank_id'                 => $tank->id,
                'reading_date'            => $data['reading_date'],
                'reading_time'            => $data['reading_time'] ?? null,
                'reading_context'         => $context,
                'previous_dip_id'         => $previousDip?->id,
                'dip_cm'                  => $dipHeight,
                'measured_liters'         => $measured,
                'book_stock_liters'       => $bookStock,
                'variance_liters'         => $variance,
                'period_variance_liters'  => $periodVariance,
                'variance_status'         => $eval['status'],
                'reason_category'         => $data['reason_category'] ?? null,
                'temperature_c'           => isset($data['temperature_c']) && $data['temperature_c'] !== '' ? (float) $data['temperature_c'] : null,
                'water_dip_mm'            => isset($data['water_dip_mm']) && $data['water_dip_mm'] !== '' ? (float) $data['water_dip_mm'] : null,
                'is_chart_calculated'     => $isChartCalculated,
                'purchase_id'             => $data['purchase_id'] ?? null,
                'financial_impact_amount' => $financialImpact,
                'recorded_by'             => $userId,
                'notes'                   => $data['notes'] ?? 'Physical tank dip verification',
            ]);

            $tank->update([
                'current_dip_reading'  => $dipHeight,
                'last_dip_date'        => $data['reading_date'],
                'last_dip_time'        => $data['reading_time'] ?? null,
                'last_measured_liters' => $measured,
                'last_variance_liters' => $variance,
                'last_variance_status' => $eval['status'],
            ]);

            ActivityLog::log(
                action: 'dip_reading',
                module: 'tanks',
                details: "Recorded Dip for {$tank->name}: {$dipHeight} {$config['dip_unit']} ({$measured} Ltr vs Book {$bookStock} Ltr). Variance: " . number_format($variance, 2) . " Ltr [{$eval['status']}]",
                userId: $userId
            );

            // Optional client-enabled policy: auto-reconcile when variance is within warning tolerance
            if (
                $config['reconciliation_mode'] === 'auto_within_tolerance'
                && $eval['status'] === 'within_tolerance'
                && !in_array($context, ['pre_delivery', 'initial'], true)
            ) {
                $this->reconcileDip(
                    $dip,
                    $userId,
                    $data['reason_category'] ?? 'measurement_tolerance',
                    'Auto-reconciled within configured tank tolerance limit',
                    true
                );
            }

            return $dip->fresh();
        });
    }

    /**
     * Reconcile tank book stock to a physical dip reading and log an immutable stock adjustment.
     */
    public function reconcileDip(
        TankDipReading $dip,
        int $userId,
        ?string $reasonCategory = null,
        ?string $notes = null,
        bool $isAuto = false
    ): TankStockAdjustment {
        return DB::transaction(function () use ($dip, $userId, $reasonCategory, $notes, $isAuto) {
            $dip = TankDipReading::lockForUpdate()->findOrFail($dip->id);
            if ($dip->is_reconciled) {
                throw ValidationException::withMessages([
                    'dip_reading' => 'This dip reading has already been reconciled.',
                ]);
            }

            $tank = Tank::lockForUpdate()->with('product')->findOrFail($dip->tank_id);
            $bookBefore = (float) $tank->current_stock;
            $targetPhysical = max(0.0, (float) $dip->measured_liters);
            $adjLiters = round($targetPhysical - $bookBefore, 2);
            $adjType = $adjLiters >= 0 ? 'gain' : 'loss';
            $unitCost = (float) ($tank->product?->purchase_price ?? 0);
            $impact = round($adjLiters * $unitCost, 2);
            $resolvedReason = $reasonCategory ?: ($dip->reason_category ?: ($adjLiters < 0 ? 'evaporation_loss' : 'temperature_variation'));

            $adjustment = TankStockAdjustment::create([
                'tank_id'             => $tank->id,
                'tank_dip_reading_id' => $dip->id,
                'adjustment_date'     => $dip->reading_date ? $dip->reading_date->toDateString() : now()->toDateString(),
                'adjustment_type'     => $adjType,
                'book_stock_before'   => $bookBefore,
                'adjustment_liters'   => $adjLiters,
                'book_stock_after'    => $targetPhysical,
                'unit_cost'           => $unitCost,
                'financial_impact'    => $impact,
                'reason_category'     => $resolvedReason,
                'notes'               => $notes ?: ($dip->notes ?: 'Book stock reconciled to physical dip measurement'),
                'is_auto'             => $isAuto,
                'created_by'          => $userId,
            ]);

            $tank->current_stock = $targetPhysical;

            // Update latest snapshot variance on tank cleanly
            $latestDip = TankDipReading::where('tank_id', $tank->id)
                ->latest('reading_date')
                ->latest('id')
                ->first();

            if (!$latestDip || $latestDip->id === $dip->id) {
                $tank->last_variance_liters = 0.00;
                $tank->last_variance_status = 'balanced';
            } else {
                $remainingVar = round((float) $latestDip->measured_liters - $targetPhysical, 2);
                $eval = $this->evaluateVariance($tank, $remainingVar, $targetPhysical);
                $tank->last_variance_liters = $remainingVar;
                $tank->last_variance_status = $eval['status'];
            }
            $tank->save();

            // Sync product stock with total stock across its storage tanks
            $this->syncProductStockFromTanks($tank->product_id);

            $dip->update([
                'is_reconciled'           => true,
                'reconciled_at'           => now(),
                'reconciled_by'           => $userId,
                'adjusted_liters'         => $adjLiters,
                'financial_impact_amount' => $impact,
                'reason_category'         => $resolvedReason,
            ]);

            $sign = $adjLiters >= 0 ? '+' : '';
            $modeLabel = $isAuto ? 'Auto-Reconciled' : 'Reconciled';
            ActivityLog::log(
                action: 'reconciled',
                module: 'tanks',
                details: "{$modeLabel} {$tank->name} book stock from " . number_format($bookBefore, 2) . " L to " . number_format($targetPhysical, 2) . " L ({$sign}" . number_format($adjLiters, 2) . " L, Cost Impact: Rs. " . number_format($impact, 2) . ")",
                userId: $userId
            );

            return $adjustment;
        });
    }

    /**
     * Synchronize Product::current_stock from all non-deleted tanks holding that product.
     */
    public function syncProductStockFromTanks(?int $productId): void
    {
        if (!$productId) {
            return;
        }

        $tanksQuery = Tank::where('product_id', $productId);
        if ($tanksQuery->count() === 0) {
            return;
        }

        $totalTankStock = round((float) $tanksQuery->sum('current_stock'), 2);
        $product = Product::lockForUpdate()->find($productId);
        if ($product) {
            $product->current_stock = max(0, $totalTankStock);
            $product->save();
        }
    }
}
