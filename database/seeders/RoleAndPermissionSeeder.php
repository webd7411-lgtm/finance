<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class RoleAndPermissionSeeder extends Seeder
{
    public static function getPermissionsGrouped(): array
    {
        return [
            'Dashboard' => [
                'dashboard.view' => 'View main dashboard & stats',
            ],
            'Operations' => [
                'shift-closings.view' => 'View shift closings',
                'shift-closings.create' => 'Create new shift closing',
                'shift-closings.unlock' => 'Lock / Unlock shift closings',
                'shift-closings.delete' => 'Delete shift closings',
                'purchases.view' => 'View purchases',
                'purchases.create' => 'Record new purchase',
                'purchases.delete' => 'Delete purchase record',
                'transactions.view' => 'View vouchers & transactions',
                'transactions.create' => 'Create payment vouchers',
                'transactions.unlock' => 'Lock / Unlock vouchers',
                'transactions.delete' => 'Delete voucher',
                'day-closings.view' => 'View day closings',
                'day-closings.create' => 'Perform day closing',
                'day-closings.reopen' => 'Reopen day closing',
            ],
            'Master Setup' => [
                'parties.view' => 'View parties & customers',
                'parties.create' => 'Create & edit parties',
                'parties.delete' => 'Delete parties',
                'accounts.view' => 'View accounts & banks',
                'accounts.create' => 'Create & edit accounts',
                'accounts.delete' => 'Delete accounts',
                'products.view' => 'View products & stock',
                'products.create' => 'Create & edit products / rates',
                'products.delete' => 'Delete products',
                'cashiers.view' => 'View cashiers',
                'cashiers.create' => 'Create & edit cashiers',
                'expense-categories.view' => 'View expense categories',
                'expense-categories.create' => 'Create & edit expense categories',
                'expense-categories.delete' => 'Delete expense categories',
            ],
            'HR Management' => [
                'hr.employees.view' => 'View employees directory',
                'hr.employees.create' => 'Create employee / user',
                'hr.employees.edit' => 'Edit employee / user',
                'hr.employees.delete' => 'Delete employee',
                'hr.attendance.view' => 'View attendance register',
                'hr.attendance.create' => 'Mark attendance & face scan',
                'hr.departments.view' => 'View departments',
                'hr.departments.create' => 'Create departments',
                'hr.departments.edit' => 'Edit departments',
                'hr.departments.delete' => 'Delete departments',
                'hr.designations.view' => 'View designations',
                'hr.designations.create' => 'Create designations',
                'hr.designations.edit' => 'Edit designations',
                'hr.designations.delete' => 'Delete designations',
                'hr.shifts.view' => 'View shifts',
                'hr.shifts.create' => 'Create shifts',
                'hr.shifts.edit' => 'Edit shifts',
                'hr.shifts.delete' => 'Delete shifts',
                'hr.holidays.view' => 'View holidays',
                'hr.holidays.create' => 'Create holidays',
                'hr.holidays.edit' => 'Edit holidays',
                'hr.holidays.delete' => 'Delete holidays',
                'hr.salary.structure.view' => 'View salary structures',
                'hr.salary.structure.create' => 'Create salary structure',
                'hr.salary.structure.edit' => 'Edit / assign salary structure',
                'hr.salary.structure.delete' => 'Delete salary structure',
                'hr.payroll.view' => 'View payroll generation',
                'hr.payroll.create' => 'Generate payroll',
                'hr.payroll.edit' => 'Edit payroll',
                'hr.payroll.delete' => 'Delete payroll',
                'hr.leaves.view' => 'View leaves',
                'hr.leaves.create' => 'Request leaves',
                'hr.leaves.edit' => 'Approve / reject leaves',
                'hr.leaves.delete' => 'Delete leaves',
                'hr.loans.view' => 'View employee loans',
                'hr.loans.create' => 'Create loans',
                'hr.loans.edit' => 'Record loan payments',
                'hr.loans.approve' => 'Approve / reject loans',
                'hr.loans.delete' => 'Delete loans',
                'hr.biometric.devices.view' => 'View biometric devices',
                'hr.biometric.devices.create' => 'Add biometric device',
                'hr.biometric.devices.edit' => 'Edit & sync biometric devices',
                'hr.biometric.devices.delete' => 'Delete biometric device',
            ],
            'Reports & Ledgers' => [
                'ledgers.party' => 'View party ledger',
                'ledgers.cash-book' => 'View cash book',
                'ledgers.bank-book' => 'View bank book',
                'reports.daily-register' => 'View daily register',
                'reports.daily-closing' => 'View daily closing summary',
                'reports.total-sale-summary' => 'View total sale summary',
                'reports.expenses' => 'View expenses report',
                'audit-logs.view' => 'View audit trail & logs',
            ],
            'Roles & Permissions' => [
                'roles.view' => 'View roles list',
                'roles.create' => 'Create custom roles',
                'roles.edit' => 'Edit roles & permissions',
                'roles.delete' => 'Delete custom roles',
            ],
        ];
    }

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create all permissions
        $grouped = self::getPermissionsGrouped();
        $allPermissionNames = [];
        foreach ($grouped as $group => $permissions) {
            foreach ($permissions as $permName => $desc) {
                Permission::firstOrCreate([
                    'name' => $permName,
                    'guard_name' => 'web',
                ]);
                $allPermissionNames[] = $permName;
            }
        }

        // 2. Create Default Roles
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $inchargeRole = Role::firstOrCreate(['name' => 'incharge', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web']);

        // Owner gets ALL permissions
        $ownerRole->syncPermissions(Permission::all());

        // Incharge gets Operations, Master Setup, Reports, HR permissions
        $inchargePermissions = array_filter($allPermissionNames, function ($perm) {
            return !in_array($perm, ['roles.create', 'roles.delete']);
        });
        $inchargeRole->syncPermissions($inchargePermissions);

        // Cashier gets Shift Closings view/create, My attendance, basic view
        $cashierPermissions = [
            'dashboard.view',
            'shift-closings.view',
            'shift-closings.create',
            'transactions.view',
            'transactions.create',
            'parties.view',
            'products.view',
            'accounts.view',
            'hr.attendance.view',
            'hr.attendance.create',
            'hr.leaves.create',
        ];
        $cashierRole->syncPermissions(array_intersect($allPermissionNames, $cashierPermissions));

        // 3. Sync existing users to their respective Spatie roles
        foreach (User::all() as $user) {
            $role = strtolower($user->role ?: 'cashier');
            if (Role::where('name', $role)->exists()) {
                $user->syncRoles([$role]);
            } else {
                $user->syncRoles(['cashier']);
            }
        }
    }
}
