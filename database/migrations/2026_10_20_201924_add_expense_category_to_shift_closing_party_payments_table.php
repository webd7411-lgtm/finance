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
        Schema::table('shift_closing_party_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('shift_closing_party_payments', 'expense_category_id')) {
                $table->foreignId('expense_category_id')
                    ->nullable()
                    ->after('party_id')
                    ->constrained('expense_categories')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_closing_party_payments', function (Blueprint $table) {
            if (Schema::hasColumn('shift_closing_party_payments', 'expense_category_id')) {
                $table->dropForeign(['expense_category_id']);
                $table->dropColumn('expense_category_id');
            }
        });
    }
};
