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
        DB::statement("ALTER TABLE shift_closings MODIFY shift_type VARCHAR(30) NOT NULL DEFAULT '24_hours'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE shift_closings MODIFY shift_type ENUM('morning', 'evening') NOT NULL DEFAULT 'morning'");
    }
};
