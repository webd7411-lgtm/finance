<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Product Categories Table
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 2. Product Units of Measurement Table
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Seed default initial categories
        $now = now();
        $defaultCategories = [
            ['name' => 'Fuel', 'slug' => 'fuel', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Lubricants & Engine Oils', 'slug' => 'lubricant', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'CNG & Auto LPG Gas', 'slug' => 'cng_lpg', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Car Care & Radiator Coolants', 'slug' => 'car_care', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tuck Shop / Convenience Items', 'slug' => 'tuck_shop', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Station Services (Wash / Greasing)', 'slug' => 'service', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'General / Miscellaneous', 'slug' => 'other', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('product_categories')->insert($defaultCategories);

        // Seed default initial units of measurement
        $defaultUnits = [
            ['name' => 'Liters (Ltr)', 'short_code' => 'liter', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cans / Tins', 'short_code' => 'can', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bottles', 'short_code' => 'bottle', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Drums (208L)', 'short_code' => 'drum', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Packs / Cartons', 'short_code' => 'pack', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pieces / Items', 'short_code' => 'piece', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Kilograms (Kg)', 'short_code' => 'kg', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
        ];
        DB::table('product_units')->insert($defaultUnits);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
        Schema::dropIfExists('product_categories');
    }
};
