<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Enhance pumps table
        Schema::table('pumps', function (Blueprint $table) {
            if (!Schema::hasColumn('pumps', 'initial_reading')) {
                $table->decimal('initial_reading', 14, 2)->default(0)->after('tank_no');
            }
            if (!Schema::hasColumn('pumps', 'nozzle_number')) {
                $table->string('nozzle_number')->nullable()->after('code');
            }
            if (!Schema::hasColumn('pumps', 'meter_type')) {
                $table->string('meter_type')->default('electronic')->after('nozzle_number'); // electronic, mechanical
            }
            if (!Schema::hasColumn('pumps', 'deleted_at')) {
                $table->softDeletes()->after('description');
            }
        });

        // 2. Add tank_no & pump_id to purchases for fuel delivery tracking
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'tank_no')) {
                $table->string('tank_no')->nullable()->after('vehicle_no');
            }
            if (!Schema::hasColumn('purchases', 'pump_id')) {
                $table->foreignId('pump_id')->nullable()->after('product_id')->constrained('pumps')->nullOnDelete();
            }
        });

        // 3. Create pump_meter_readings audit history table
        if (!Schema::hasTable('pump_meter_readings')) {
            Schema::create('pump_meter_readings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pump_id')->constrained('pumps')->cascadeOnDelete();
                $table->foreignId('shift_closing_id')->nullable()->constrained('shift_closings')->nullOnDelete();
                $table->date('reading_date');
                $table->string('reading_type')->default('shift_sale'); // shift_sale, calibration, manual_adjustment, initial_setup
                
                $table->decimal('start_reading', 14, 2)->default(0);
                $table->decimal('end_reading', 14, 2)->default(0);
                $table->decimal('volume_dispensed', 14, 2)->default(0);
                $table->decimal('fuel_rate', 12, 2)->default(0);
                $table->decimal('sales_amount', 14, 2)->default(0);
                
                $table->foreignId('cashier_id')->nullable()->constrained('cashiers')->nullOnDelete();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->index(['pump_id', 'reading_date']);
                $table->index('shift_closing_id');
                $table->index('reading_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pump_meter_readings');

        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'pump_id')) {
                $table->dropForeign(['pump_id']);
                $table->dropColumn('pump_id');
            }
            if (Schema::hasColumn('purchases', 'tank_no')) {
                $table->dropColumn('tank_no');
            }
        });

        Schema::table('pumps', function (Blueprint $table) {
            if (Schema::hasColumn('pumps', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            $cols = array_filter(['initial_reading', 'nozzle_number', 'meter_type'], fn ($c) => Schema::hasColumn('pumps', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
