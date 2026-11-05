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
            $table->foreignId('original_transaction_id')->nullable()->after('type')->constrained('transactions')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['original_transaction_id']);
            }
            $table->dropColumn('original_transaction_id');
        });
    }
};
