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
        Schema::table('day_closings', function (Blueprint $table) {
            $table->decimal('bank_opening', 15, 2)->default(0.00)->after('opening_cash');
            $table->decimal('jazzcash_opening', 15, 2)->default(0.00)->after('bank_opening');
            $table->decimal('total_opening_all_accounts', 15, 2)->default(0.00)->after('jazzcash_opening');

            $table->decimal('bank_in', 15, 2)->default(0.00)->after('total_cash_in');
            $table->decimal('jazzcash_in', 15, 2)->default(0.00)->after('bank_in');
            $table->decimal('total_in_all_accounts', 15, 2)->default(0.00)->after('jazzcash_in');

            $table->decimal('bank_out', 15, 2)->default(0.00)->after('total_payments_out');
            $table->decimal('jazzcash_out', 15, 2)->default(0.00)->after('bank_out');
            $table->decimal('total_out_all_accounts', 15, 2)->default(0.00)->after('jazzcash_out');

            $table->decimal('bank_closing', 15, 2)->default(0.00)->after('closing_cash');
            $table->decimal('jazzcash_closing', 15, 2)->default(0.00)->after('bank_closing');
            $table->decimal('total_closing_all_accounts', 15, 2)->default(0.00)->after('jazzcash_closing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('day_closings', function (Blueprint $table) {
            $table->dropColumn([
                'bank_opening',
                'jazzcash_opening',
                'total_opening_all_accounts',
                'bank_in',
                'jazzcash_in',
                'total_in_all_accounts',
                'bank_out',
                'jazzcash_out',
                'total_out_all_accounts',
                'bank_closing',
                'jazzcash_closing',
                'total_closing_all_accounts',
            ]);
        });
    }
};
