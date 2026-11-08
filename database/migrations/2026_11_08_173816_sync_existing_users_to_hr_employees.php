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
        // 1. Ensure basic departments exist
        $mgmtDeptId = DB::table('hr_departments')->where('name', 'Management')->value('id');
        if (!$mgmtDeptId) {
            $mgmtDeptId = DB::table('hr_departments')->insertGetId([
                'name' => 'Management',
                'description' => 'Executive & Admin Management',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $opsDeptId = DB::table('hr_departments')->where('name', 'Operations')->value('id');
        if (!$opsDeptId) {
            $opsDeptId = DB::table('hr_departments')->insertGetId([
                'name' => 'Operations',
                'description' => 'Station Operations & Shift Sales',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Ensure basic designations exist
        $ownerDesigId = DB::table('hr_designations')->where('name', 'Station Owner')->value('id');
        if (!$ownerDesigId) {
            $ownerDesigId = DB::table('hr_designations')->insertGetId([
                'name' => 'Station Owner',
                'is_sale_officer' => false,
                'description' => 'Owner & System Administrator',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $inchargeDesigId = DB::table('hr_designations')->where('name', 'Branch Incharge')->value('id');
        if (!$inchargeDesigId) {
            $inchargeDesigId = DB::table('hr_designations')->insertGetId([
                'name' => 'Branch Incharge',
                'is_sale_officer' => false,
                'description' => 'Station Supervisor & Operations Incharge',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $cashierDesigId = DB::table('hr_designations')->where('name', 'Shift Cashier')->value('id');
        if (!$cashierDesigId) {
            $cashierDesigId = DB::table('hr_designations')->insertGetId([
                'name' => 'Shift Cashier',
                'is_sale_officer' => true,
                'description' => 'Counter Cashier & Fuel Dispenser Operator',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Ensure a default shift exists
        $shiftId = DB::table('hr_shifts')->value('id');
        if (!$shiftId) {
            $shiftId = DB::table('hr_shifts')->insertGetId([
                'name' => 'Regular Shift (08:00 - 20:00)',
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
                'grace_minutes' => 15,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 4. Safely populate existing users into hr_employees
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $existsInEmployees = DB::table('hr_employees')
                ->where('user_id', $user->id)
                ->orWhere('email', $user->email)
                ->exists();

            if (!$existsInEmployees) {
                $nameParts = explode(' ', trim($user->name), 2);
                $firstName = $nameParts[0] ?? 'User';
                $lastName = $nameParts[1] ?? ($user->role ? ucfirst($user->role) : 'Officer');

                $desigId = match ($user->role) {
                    'owner' => $ownerDesigId,
                    'incharge' => $inchargeDesigId,
                    default => $cashierDesigId,
                };

                $deptId = match ($user->role) {
                    'owner' => $mgmtDeptId,
                    default => $opsDeptId,
                };

                $joiningDate = $user->created_at ? date('Y-m-d', strtotime($user->created_at)) : now()->toDateString();

                DB::table('hr_employees')->insert([
                    'user_id' => $user->id,
                    'department_id' => $deptId,
                    'designation_id' => $desigId,
                    'shift_id' => $shiftId,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'phone' => '0300-0000000',
                    'joining_date' => $joiningDate,
                    'status' => ($user->status === 'active') ? 'active' : 'non-active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Sync cashier into cashiers table for shift closings
            if ($user->role === 'cashier') {
                DB::table('cashiers')->updateOrInsert(
                    ['id' => $user->id],
                    [
                        'name' => $user->name,
                        'code' => 'CSH-' . str_pad($user->id, 3, '0', STR_PAD_LEFT),
                        'status' => $user->status ?? 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe reversible: do not delete data
    }
};
