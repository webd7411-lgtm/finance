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
        // 1. Table for Fuel Rate Revisions & Inventory Revaluation (Stock Gain / Loss at 12:00 AM Midnight)
        if (!Schema::hasTable('fuel_rate_revisions')) {
            Schema::create('fuel_rate_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('affected_tank_id')->nullable()->constrained('tanks')->nullOnDelete();
                $table->date('effective_date');
                $table->string('effective_time', 20)->default('00:00:00'); // Midnight 12:00 AM
                $table->decimal('old_sale_rate', 10, 2);
                $table->decimal('new_sale_rate', 10, 2);
                $table->decimal('rate_difference', 10, 2); // (new - old): positive for gain, negative for loss
                $table->decimal('old_purchase_rate', 10, 2)->nullable();
                $table->decimal('new_purchase_rate', 10, 2)->nullable();
                $table->decimal('stock_at_revision', 12, 2)->default(0); // Liters in storage at midnight
                $table->decimal('stock_gain_loss_amount', 14, 2)->default(0); // (stock_at_revision * rate_difference)
                $table->string('notification_ref', 255)->nullable(); // e.g. "OGRA Circular / OMC Notification"
                $table->text('remarks')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['effective_date', 'product_id']);
            });
        }

        // 2. Add Mid-Shift Midnight Rate Change fields to shift_closing_nozzles
        Schema::table('shift_closing_nozzles', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_closing_nozzles', 'has_rate_change')) {
                $table->boolean('has_rate_change')->default(false)->after('subtotal');
            }
            if (!Schema::hasColumn('shift_closing_nozzles', 'midnight_reading')) {
                $table->decimal('midnight_reading', 12, 2)->nullable()->after('has_rate_change');
            }
            if (!Schema::hasColumn('shift_closing_nozzles', 'old_fuel_rate')) {
                $table->decimal('old_fuel_rate', 10, 2)->nullable()->after('midnight_reading');
            }
            if (!Schema::hasColumn('shift_closing_nozzles', 'new_fuel_rate')) {
                $table->decimal('new_fuel_rate', 10, 2)->nullable()->after('old_fuel_rate');
            }
            if (!Schema::hasColumn('shift_closing_nozzles', 'old_rate_liters')) {
                $table->decimal('old_rate_liters', 12, 2)->nullable()->after('new_fuel_rate');
            }
            if (!Schema::hasColumn('shift_closing_nozzles', 'new_rate_liters')) {
                $table->decimal('new_rate_liters', 12, 2)->nullable()->after('old_rate_liters');
            }
            if (!Schema::hasColumn('shift_closing_nozzles', 'old_rate_subtotal')) {
                $table->decimal('old_rate_subtotal', 12, 2)->nullable()->after('new_rate_liters');
            }
            if (!Schema::hasColumn('shift_closing_nozzles', 'new_rate_subtotal')) {
                $table->decimal('new_rate_subtotal', 12, 2)->nullable()->after('old_rate_subtotal');
            }
        });

        // 3. Add shift-level rate revision flag to shift_closings
        Schema::table('shift_closings', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_closings', 'has_rate_change')) {
                $table->boolean('has_rate_change')->default(false)->after('fuel_rate');
            }
            if (!Schema::hasColumn('shift_closings', 'rate_difference_amount')) {
                $table->decimal('rate_difference_amount', 14, 2)->default(0)->after('has_rate_change');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            $cols = ['has_rate_change', 'rate_difference_amount'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('shift_closings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('shift_closing_nozzles', function (Blueprint $table) {
            $cols = [
                'has_rate_change',
                'midnight_reading',
                'old_fuel_rate',
                'new_fuel_rate',
                'old_rate_liters',
                'new_rate_liters',
                'old_rate_subtotal',
                'new_rate_subtotal'
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('shift_closing_nozzles', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('fuel_rate_revisions');
    }
};
