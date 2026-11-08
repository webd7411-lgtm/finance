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
        Schema::create('pumps', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. Pump Machine 1 (Super Petrol), Dispenser 2 (HSD)
            $table->string('code')->nullable(); // e.g. PUMP-01, NOZZLE-01
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('tank_no')->nullable(); // e.g. Underground Tank #1
            $table->decimal('last_reading', 14, 2)->default(0);
            $table->string('status')->default('active'); // active, inactive
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pumps');
    }
};
