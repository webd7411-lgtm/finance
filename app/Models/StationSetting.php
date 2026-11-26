<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    public const TANK_DEFAULTS = [
        'tank_dip_unit'                   => 'mm',
        'tank_tolerance_mode'             => 'higher_of',
        'tank_warning_liters'             => '25',
        'tank_critical_liters'            => '50',
        'tank_warning_percent'            => '0.50',
        'tank_critical_percent'           => '1.00',
        'tank_consecutive_shortage_alert' => '3',
        'tank_reconciliation_mode'        => 'manual_approval',
        'tank_bowser_dip_mode'            => 'optional',
        'tank_show_variance_on_dashboard' => '1',
    ];

    public const TOLERANCE_MODES = [
        'fixed_liters' => 'Fixed Liters Only (e.g. ±25 Ltr)',
        'percentage'   => 'Percentage of Stock Only (e.g. ±0.5%)',
        'higher_of'    => 'Whichever is Higher (Recommended)',
        'lower_of'     => 'Whichever is Lower (Strict)',
    ];

    public const RECONCILIATION_MODES = [
        'manual_approval'       => 'Manual Owner / Incharge Approval Only (Recommended)',
        'auto_within_tolerance' => 'Auto-Reconcile Within Warning Tolerance (Require Approval Above)',
    ];

    public const BOWSER_DIP_MODES = [
        'disabled' => 'Disabled (Hide on Fuel Purchase)',
        'optional' => 'Optional Verification Section (Recommended)',
        'required' => 'Required on All Bulk Fuel Purchases',
    ];

    public const READING_CONTEXTS = [
        'daily_routine'        => 'Daily Routine Check',
        'morning_opening'      => 'Morning Opening Dip',
        'evening_closing'      => 'Evening Closing Dip',
        'pre_delivery'         => 'Pre-Delivery (Before Bowser)',
        'post_delivery'        => 'Post-Delivery (After Settling)',
        'audit_reconciliation' => 'Audit / Month-End Verification',
        'initial'              => 'Initial Setup Calibration',
    ];

    public const REASON_CATEGORIES = [
        'temperature_variation' => 'Temperature Expansion / Contraction',
        'evaporation_loss'      => 'Normal Evaporation / Breathing Loss',
        'measurement_tolerance' => 'Dip Rod / Calibration Chart Tolerance',
        'meter_calibration'     => 'Dispenser Meter Calibration Drift',
        'tanker_shortage'       => 'OMC Bowser / Tanker Short or Excess Delivery',
        'suspected_leak_theft'  => 'Suspected Leakage or Pilferage',
        'other'                 => 'Other Operational Reason',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        try {
            $row = static::where('key', $key)->first();
            return $row ? $row->value : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function setValue(string $key, mixed $value, ?int $userId = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value'      => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                'updated_by' => $userId ?? auth()->id(),
            ]
        );
    }

    /**
     * Load all tank & dip configuration settings with safe defaults.
     */
    public static function getTankConfig(): array
    {
        $config = self::TANK_DEFAULTS;

        try {
            $rows = static::whereIn('key', array_keys(self::TANK_DEFAULTS))->pluck('value', 'key');
            foreach ($rows as $k => $v) {
                if ($v !== null && $v !== '') {
                    $config[$k] = $v;
                }
            }
        } catch (\Throwable $e) {
            // Table may not exist yet during early migration checks
        }

        return [
            'dip_unit'                   => in_array($config['tank_dip_unit'], ['mm', 'cm'], true) ? $config['tank_dip_unit'] : 'mm',
            'tolerance_mode'             => array_key_exists($config['tank_tolerance_mode'], self::TOLERANCE_MODES) ? $config['tank_tolerance_mode'] : 'higher_of',
            'warning_liters'             => max(0.0, (float) $config['tank_warning_liters']),
            'critical_liters'            => max(0.0, (float) $config['tank_critical_liters']),
            'warning_percent'            => max(0.0, (float) $config['tank_warning_percent']),
            'critical_percent'           => max(0.0, (float) $config['tank_critical_percent']),
            'consecutive_shortage_alert' => max(0, (int) $config['tank_consecutive_shortage_alert']),
            'reconciliation_mode'        => array_key_exists($config['tank_reconciliation_mode'], self::RECONCILIATION_MODES) ? $config['tank_reconciliation_mode'] : 'manual_approval',
            'bowser_dip_mode'            => array_key_exists($config['tank_bowser_dip_mode'], self::BOWSER_DIP_MODES) ? $config['tank_bowser_dip_mode'] : 'optional',
            'show_variance_on_dashboard' => in_array((string) $config['tank_show_variance_on_dashboard'], ['1', 'true', 'on'], true),
        ];
    }
}
