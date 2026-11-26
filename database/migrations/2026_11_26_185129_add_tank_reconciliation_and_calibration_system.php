<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Station Settings table (Client-configurable operational rules)
        if (!Schema::hasTable('station_settings')) {
            Schema::create('station_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });

            $now = now();
            $defaults = [
                'tank_dip_unit'                    => 'mm',
                'tank_tolerance_mode'              => 'higher_of',
                'tank_warning_liters'              => '25',
                'tank_critical_liters'             => '50',
                'tank_warning_percent'             => '0.50',
                'tank_critical_percent'            => '1.00',
                'tank_consecutive_shortage_alert'  => '3',
                'tank_reconciliation_mode'         => 'manual_approval',
                'tank_bowser_dip_mode'             => 'optional',
                'tank_show_variance_on_dashboard'  => '1',
            ];

            foreach ($defaults as $k => $v) {
                DB::table('station_settings')->insertOrIgnore([
                    'key'        => $k,
                    'value'      => $v,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2. Enhance tanks table with per-tank tolerance overrides & latest dip snapshot
        Schema::table('tanks', function (Blueprint $table) {
            if (!Schema::hasColumn('tanks', 'use_default_tolerance')) {
                $table->boolean('use_default_tolerance')->default(true)->after('max_dip_reading');
            }
            if (!Schema::hasColumn('tanks', 'tolerance_mode')) {
                $table->string('tolerance_mode', 30)->nullable()->after('use_default_tolerance');
            }
            if (!Schema::hasColumn('tanks', 'warning_tolerance_liters')) {
                $table->decimal('warning_tolerance_liters', 10, 2)->nullable()->after('tolerance_mode');
            }
            if (!Schema::hasColumn('tanks', 'critical_tolerance_liters')) {
                $table->decimal('critical_tolerance_liters', 10, 2)->nullable()->after('warning_tolerance_liters');
            }
            if (!Schema::hasColumn('tanks', 'warning_tolerance_percent')) {
                $table->decimal('warning_tolerance_percent', 6, 2)->nullable()->after('critical_tolerance_liters');
            }
            if (!Schema::hasColumn('tanks', 'critical_tolerance_percent')) {
                $table->decimal('critical_tolerance_percent', 6, 2)->nullable()->after('warning_tolerance_percent');
            }
            if (!Schema::hasColumn('tanks', 'last_dip_date')) {
                $table->date('last_dip_date')->nullable()->after('critical_tolerance_percent');
            }
            if (!Schema::hasColumn('tanks', 'last_dip_time')) {
                $table->time('last_dip_time')->nullable()->after('last_dip_date');
            }
            if (!Schema::hasColumn('tanks', 'last_measured_liters')) {
                $table->decimal('last_measured_liters', 14, 2)->nullable()->after('last_dip_time');
            }
            if (!Schema::hasColumn('tanks', 'last_variance_liters')) {
                $table->decimal('last_variance_liters', 14, 2)->nullable()->after('last_measured_liters');
            }
            if (!Schema::hasColumn('tanks', 'last_variance_status')) {
                $table->string('last_variance_status', 30)->nullable()->after('last_variance_liters');
            }
        });

        // 3. Per-Tank Dip Calibration Charts (mm -> liters strapping table)
        if (!Schema::hasTable('tank_dip_charts')) {
            Schema::create('tank_dip_charts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
                $table->decimal('dip_mm', 10, 2);
                $table->decimal('liters', 14, 2);
                $table->timestamps();

                $table->unique(['tank_id', 'dip_mm']);
                $table->index(['tank_id', 'dip_mm']);
            });
        }

        // 4. Enhance tank_dip_readings with context, period variance, tolerance status & reconciliation metadata
        Schema::table('tank_dip_readings', function (Blueprint $table) {
            if (!Schema::hasColumn('tank_dip_readings', 'reading_context')) {
                $table->string('reading_context', 40)->default('daily_routine')->after('reading_time');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'previous_dip_id')) {
                $table->foreignId('previous_dip_id')->nullable()->after('reading_context')->constrained('tank_dip_readings')->nullOnDelete();
            }
            if (!Schema::hasColumn('tank_dip_readings', 'period_variance_liters')) {
                $table->decimal('period_variance_liters', 14, 2)->nullable()->after('variance_liters');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'variance_status')) {
                $table->string('variance_status', 30)->default('within_tolerance')->after('period_variance_liters');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'reason_category')) {
                $table->string('reason_category', 60)->nullable()->after('variance_status');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'temperature_c')) {
                $table->decimal('temperature_c', 6, 2)->nullable()->after('reason_category');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'water_dip_mm')) {
                $table->decimal('water_dip_mm', 8, 2)->nullable()->after('temperature_c');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'is_chart_calculated')) {
                $table->boolean('is_chart_calculated')->default(false)->after('water_dip_mm');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'purchase_id')) {
                $table->foreignId('purchase_id')->nullable()->after('is_chart_calculated')->constrained('purchases')->nullOnDelete();
            }
            if (!Schema::hasColumn('tank_dip_readings', 'is_reconciled')) {
                $table->boolean('is_reconciled')->default(false)->after('purchase_id');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'reconciled_at')) {
                $table->timestamp('reconciled_at')->nullable()->after('is_reconciled');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'reconciled_by')) {
                $table->foreignId('reconciled_by')->nullable()->after('reconciled_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('tank_dip_readings', 'adjusted_liters')) {
                $table->decimal('adjusted_liters', 14, 2)->nullable()->after('reconciled_by');
            }
            if (!Schema::hasColumn('tank_dip_readings', 'financial_impact_amount')) {
                $table->decimal('financial_impact_amount', 14, 2)->nullable()->after('adjusted_liters');
            }
        });

        // 5. Immutable Tank Stock Adjustments ledger (approved book-to-physical reconciliations)
        if (!Schema::hasTable('tank_stock_adjustments')) {
            Schema::create('tank_stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tank_id')->constrained('tanks')->cascadeOnDelete();
                $table->foreignId('tank_dip_reading_id')->nullable()->constrained('tank_dip_readings')->nullOnDelete();
                $table->date('adjustment_date');
                $table->string('adjustment_type', 20); // gain, loss
                $table->decimal('book_stock_before', 14, 2)->default(0);
                $table->decimal('adjustment_liters', 14, 2)->default(0); // signed: +gain, -loss
                $table->decimal('book_stock_after', 14, 2)->default(0);
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->decimal('financial_impact', 14, 2)->default(0); // signed Rs.
                $table->string('reason_category', 60)->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_auto')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tank_id', 'adjustment_date']);
            });
        }

        // 6. Optional Pre-Delivery & Post-Delivery Dip verification fields on purchases
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'pre_delivery_dip_mm')) {
                $table->decimal('pre_delivery_dip_mm', 10, 2)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('purchases', 'pre_delivery_liters')) {
                $table->decimal('pre_delivery_liters', 14, 2)->nullable()->after('pre_delivery_dip_mm');
            }
            if (!Schema::hasColumn('purchases', 'post_delivery_dip_mm')) {
                $table->decimal('post_delivery_dip_mm', 10, 2)->nullable()->after('pre_delivery_liters');
            }
            if (!Schema::hasColumn('purchases', 'post_delivery_liters')) {
                $table->decimal('post_delivery_liters', 14, 2)->nullable()->after('post_delivery_dip_mm');
            }
            if (!Schema::hasColumn('purchases', 'sales_during_unloading')) {
                $table->decimal('sales_during_unloading', 14, 2)->default(0)->after('post_delivery_liters');
            }
            if (!Schema::hasColumn('purchases', 'actual_received_liters')) {
                $table->decimal('actual_received_liters', 14, 2)->nullable()->after('sales_during_unloading');
            }
            if (!Schema::hasColumn('purchases', 'delivery_variance_liters')) {
                $table->decimal('delivery_variance_liters', 14, 2)->nullable()->after('actual_received_liters');
            }
        });

        // 7. Backfill existing tanks' latest dip snapshot from historical tank_dip_readings if present
        $tanks = DB::table('tanks')->get();
        foreach ($tanks as $t) {
            $latest = DB::table('tank_dip_readings')
                ->where('tank_id', $t->id)
                ->orderByDesc('reading_date')
                ->orderByDesc('id')
                ->first();

            if ($latest) {
                $var = (float) $latest->variance_liters;
                $status = abs($var) < 0.01 ? 'balanced' : (abs($var) <= 25 ? 'within_tolerance' : (abs($var) <= 50 ? 'warning' : 'critical'));
                DB::table('tanks')->where('id', $t->id)->update([
                    'last_dip_date'        => $latest->reading_date,
                    'last_dip_time'        => $latest->reading_time,
                    'last_measured_liters' => $latest->measured_liters,
                    'last_variance_liters' => $latest->variance_liters,
                    'last_variance_status' => $status,
                ]);
                DB::table('tank_dip_readings')->where('id', $latest->id)->update([
                    'variance_status' => $status,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $cols = [
                'pre_delivery_dip_mm',
                'pre_delivery_liters',
                'post_delivery_dip_mm',
                'post_delivery_liters',
                'sales_during_unloading',
                'actual_received_liters',
                'delivery_variance_liters',
            ];
            $existing = array_filter($cols, fn ($c) => Schema::hasColumn('purchases', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::dropIfExists('tank_stock_adjustments');

        Schema::table('tank_dip_readings', function (Blueprint $table) {
            foreach (['previous_dip_id', 'purchase_id', 'reconciled_by'] as $fkCol) {
                if (Schema::hasColumn('tank_dip_readings', $fkCol)) {
                    $table->dropConstrainedForeignId($fkCol);
                }
            }
            $cols = [
                'reading_context',
                'period_variance_liters',
                'variance_status',
                'reason_category',
                'temperature_c',
                'water_dip_mm',
                'is_chart_calculated',
                'is_reconciled',
                'reconciled_at',
                'adjusted_liters',
                'financial_impact_amount',
            ];
            $existing = array_filter($cols, fn ($c) => Schema::hasColumn('tank_dip_readings', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::dropIfExists('tank_dip_charts');

        Schema::table('tanks', function (Blueprint $table) {
            $cols = [
                'use_default_tolerance',
                'tolerance_mode',
                'warning_tolerance_liters',
                'critical_tolerance_liters',
                'warning_tolerance_percent',
                'critical_tolerance_percent',
                'last_dip_date',
                'last_dip_time',
                'last_measured_liters',
                'last_variance_liters',
                'last_variance_status',
            ];
            $existing = array_filter($cols, fn ($c) => Schema::hasColumn('tanks', $c));
            if (!empty($existing)) {
                $table->dropColumn($existing);
            }
        });

        Schema::dropIfExists('station_settings');
    }
};
