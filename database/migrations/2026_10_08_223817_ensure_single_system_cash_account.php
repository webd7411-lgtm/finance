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
        $cashAccounts = \Illuminate\Support\Facades\DB::table('accounts')->where('type', 'cash')->get();
        if ($cashAccounts->isEmpty()) {
            \Illuminate\Support\Facades\DB::table('accounts')->insert([
                'name' => 'Cash in Hand',
                'type' => 'cash',
                'account_number' => null,
                'opening_balance' => 0.00,
                'current_balance' => 0.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            \Illuminate\Support\Facades\DB::table('accounts')
                ->where('type', 'cash')
                ->where('name', 'cash')
                ->update(['name' => 'Cash in Hand']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
