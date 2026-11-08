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
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->foreignId('pump_id')->nullable()->constrained('pumps')->nullOnDelete();
            $table->string('pump_machine')->nullable(); // Pump machine name / label
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->decimal('start_reading', 14, 2)->nullable();
            $table->decimal('end_reading', 14, 2)->nullable();
            $table->decimal('total_liters', 14, 2)->default(0);
            $table->decimal('fuel_rate', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['pump_id']);
                $table->dropForeign(['product_id']);
            }
            $table->dropColumn([
                'pump_id',
                'pump_machine',
                'product_id',
                'start_reading',
                'end_reading',
                'total_liters',
                'fuel_rate',
            ]);
        });
    }
};
