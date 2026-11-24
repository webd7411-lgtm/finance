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
        Schema::create('rental_facility_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable()->default('🏢');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['name' => 'Car Wash Station', 'slug' => 'car_wash', 'icon' => '🚿', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tyre Shop & Puncture', 'slug' => 'tyre_shop', 'icon' => '🛞', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Mart / Tuck Shop', 'slug' => 'tuck_shop', 'icon' => '🏪', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'ATM Booth Space', 'slug' => 'atm', 'icon' => '🏧', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Other Leased Space', 'slug' => 'other', 'icon' => '🏢', 'is_default' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('rental_facility_types')->insert($defaults);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_facility_types');
    }
};
