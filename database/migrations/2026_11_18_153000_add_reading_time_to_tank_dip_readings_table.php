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
        Schema::table('tank_dip_readings', function (Blueprint $table) {
            $table->time('reading_time')->nullable()->after('reading_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tank_dip_readings', function (Blueprint $table) {
            $table->dropColumn('reading_time');
        });
    }
};
