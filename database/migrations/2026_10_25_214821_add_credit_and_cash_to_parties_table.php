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
        Schema::table('parties', function (Blueprint $table) {
            if (!Schema::hasColumn('parties', 'is_credit')) {
                $table->boolean('is_credit')->default(true)->after('type');
            }
            if (!Schema::hasColumn('parties', 'is_cash')) {
                $table->boolean('is_cash')->default(false)->after('is_credit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parties', function (Blueprint $table) {
            if (Schema::hasColumn('parties', 'is_cash')) {
                $table->dropColumn('is_cash');
            }
            if (Schema::hasColumn('parties', 'is_credit')) {
                $table->dropColumn('is_credit');
            }
        });
    }
};
