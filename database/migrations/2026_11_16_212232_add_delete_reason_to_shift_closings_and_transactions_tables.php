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
            $table->text('delete_reason')->nullable()->after('deleted_by');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->text('delete_reason')->nullable()->after('deleted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_closings', function (Blueprint $table) {
            $table->dropColumn('delete_reason');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('delete_reason');
        });
    }
};
