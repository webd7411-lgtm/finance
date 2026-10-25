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
        if (!Schema::hasTable('cashiers')) {
            Schema::create('cashiers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('code')->nullable();
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Migrate existing cashier users into cashiers table to preserve data integrity
        $users = DB::table('users')->where('role', 'cashier')->get();
        foreach ($users as $user) {
            DB::table('cashiers')->updateOrInsert(
                ['id' => $user->id],
                [
                    'name' => $user->name,
                    'phone' => null,
                    'code' => 'CSH-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                    'status' => $user->status ?? 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Ensure users who have already closed shifts also exist in cashiers table
        $shiftCashierIds = DB::table('shift_closings')->pluck('cashier_id')->unique();
        foreach ($shiftCashierIds as $uId) {
            if (!DB::table('cashiers')->where('id', $uId)->exists()) {
                $u = DB::table('users')->where('id', $uId)->first();
                if ($u) {
                    DB::table('cashiers')->insert([
                        'id' => $u->id,
                        'name' => $u->name,
                        'phone' => null,
                        'code' => 'CSH-' . str_pad($u->id, 3, '0', STR_PAD_LEFT),
                        'status' => 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Drop foreign key to users so cashier_id can link to cashiers
        if (Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
            Schema::table('shift_closings', function (Blueprint $table) {
                try {
                    $table->dropForeign(['cashier_id']);
                } catch (\Throwable $e) {
                    // foreign key may not exist or named differently
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashiers');
    }
};
