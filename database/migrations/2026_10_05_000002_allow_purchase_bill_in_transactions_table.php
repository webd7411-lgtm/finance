<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Allow purchase_bill in type enum, and make account_id nullable for purchase bills
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('payment_in', 'payment_out', 'purchase_bill') NOT NULL");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN account_id BIGINT UNSIGNED NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE transactions MODIFY COLUMN account_id BIGINT UNSIGNED NOT NULL");
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('payment_in', 'payment_out') NOT NULL");
        }
    }
};
