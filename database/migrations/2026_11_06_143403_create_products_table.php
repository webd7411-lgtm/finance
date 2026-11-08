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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('category')->default('fuel'); // fuel, lubricant, cng_lpg, car_care, tuck_shop, service, other
            $table->string('unit')->default('liter');     // liter, can, bottle, drum, pack, piece, kg
            $table->boolean('is_fuel')->default(false);
            $table->string('density_or_octane')->nullable(); // e.g. RON 92, 20W-50, 0.835 kg/L
            $table->string('tank_or_shelf')->nullable();     // e.g. Tank 1 (Underground), Rack A-1
            $table->foreignId('supplier_id')->nullable()->constrained('parties')->nullOnDelete();
            
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('opening_stock', 12, 2)->default(0);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->decimal('min_stock_alert', 12, 2)->nullable();
            
            $table->string('status')->default('active'); // active, inactive
            $table->text('description')->nullable();
            
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('category');
            $table->index('status');
            $table->index('is_fuel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
