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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('deleted_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->softDeletes()->after('deleted_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['deleted_by']);
            }
            $table->dropColumn('deleted_by');
            $table->dropSoftDeletes();
        });
    }
};
