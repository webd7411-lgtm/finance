<?php

use App\Models\ShiftClosing;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PumpController;
use App\Http\Controllers\TankController;
use App\Http\Controllers\StationLocationController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ShiftClosingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DayClosingController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Step 1: Users & Roles Module (Merged into HR Management - redirect to Employees Directory)
    Route::middleware('role:owner,incharge')->group(function () {
        Route::get('users', function () {
            return redirect()->route('hr.employees.index');
        })->name('users.index');

        // Roles & Permissions Management (three-star UI)
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::put('roles/{id}', [RoleController::class, 'update'])->name('roles.update');
        Route::post('roles/update-permissions', [RoleController::class, 'updatePermissions'])->name('roles.update.permission');
        Route::delete('roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
        Route::get('roles/delete/{id}', [RoleController::class, 'delete'])->name('roles.delete');
    });

    // Step 2: Master Setup (Parties, Accounts, Categories, Products)
    Route::get('parties/check-duplicate', [PartyController::class, 'checkDuplicate'])->name('parties.check-duplicate');
    Route::resource('parties', PartyController::class);
    Route::resource('accounts', AccountController::class);
    Route::post('accounts/{account}/toggle-status', [AccountController::class, 'toggleStatus'])->name('accounts.toggle-status');
    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['create', 'show', 'edit']);
    Route::resource('cashiers', CashierController::class)->except(['create', 'show', 'edit']);
    Route::post('cashiers/{cashier}/toggle-status', [CashierController::class, 'toggleStatus'])->name('cashiers.toggle-status');
    
    // Petrol Pump Products & Inventory
    Route::get('products/check-duplicate', [ProductController::class, 'checkDuplicate'])->name('products.check-duplicate');
    Route::post('products/{product}/toggle-status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
    Route::post('products/{product}/quick-price', [ProductController::class, 'quickPriceUpdate'])->name('products.quick-price');
    Route::post('products/categories/quick-store', [ProductController::class, 'storeCategory'])->name('products.categories.quick-store');
    Route::post('products/units/quick-store', [ProductController::class, 'storeUnit'])->name('products.units.quick-store');
    Route::resource('products', ProductController::class);

    // Station Locations & Sites
    Route::resource('locations', StationLocationController::class)->except(['create', 'show', 'edit']);

    // Storage Tanks & Dip Readings
    Route::post('tanks/{tank}/record-dip', [TankController::class, 'recordDip'])->name('tanks.record-dip');
    Route::resource('tanks', TankController::class);

    // Stock & Fuel Purchases
    Route::resource('purchases', PurchaseController::class)->except(['edit', 'update']);

    // Petrol Pump Machines & Meter Readings Management
    Route::get('pumps/details/{pump}', [PumpController::class, 'getDetails'])->name('pumps.details');
    Route::post('pumps/{pump}/toggle-status', [PumpController::class, 'toggleStatus'])->name('pumps.toggle-status');
    Route::post('pumps/{pump}/adjust-reading', [PumpController::class, 'adjustReading'])->name('pumps.adjust-reading');
    Route::post('pumps/quick-store', [PumpController::class, 'store'])->name('pumps.quick-store');
    Route::resource('pumps', PumpController::class);

    // Step 3: Shift Closing Module (Morning & Evening)
    Route::resource('shift-closings', ShiftClosingController::class)->except(['edit', 'update']);
    Route::post('shift-closings/{shiftClosing}/unlock', [ShiftClosingController::class, 'unlock'])->name('shift-closings.unlock');
    Route::post('shift-closings/{shiftClosing}/lock', [ShiftClosingController::class, 'lock'])->name('shift-closings.lock');

    // Step 4: Daily Vouchers (Payment In & Payment Out)
    Route::post('transactions/transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');
    Route::post('transactions/{transaction}/unlock', [TransactionController::class, 'unlock'])->name('transactions.unlock');
    Route::post('transactions/{transaction}/lock', [TransactionController::class, 'lock'])->name('transactions.lock');
    Route::resource('transactions', TransactionController::class)->except(['edit', 'update', 'create']);

    // Step 5: Day Closing Module (Dono Shifts Merge & Auto Opening)
    Route::resource('day-closings', DayClosingController::class)->except(['edit', 'update', 'create']);
    Route::post('day-closings/{dayClosing}/reopen', [DayClosingController::class, 'reopen'])->name('day-closings.reopen');

    // Step 6: Ledgers & Reports Module
    Route::get('ledgers/party/{party?}', [LedgerController::class, 'partyLedger'])->name('ledgers.party');
    Route::get('ledgers/cash-book', [LedgerController::class, 'cashBook'])->name('ledgers.cash-book');
    Route::get('ledgers/bank-book', [LedgerController::class, 'bankBook'])->name('ledgers.bank-book');

    Route::get('reports/daily-closing', [ReportController::class, 'dailyClosingSummary'])->name('reports.daily-closing');
    Route::get('reports/daily-register', [ReportController::class, 'dailyRegister'])->name('reports.daily-register');
    Route::get('reports/daily-register/export', [ReportController::class, 'exportDailyRegisterCsv'])->name('reports.daily-register.export');
    Route::get('reports/total-sale-summary', [ReportController::class, 'totalSaleSummary'])->name('reports.total-sale-summary');
    Route::get('reports/expenses', [ReportController::class, 'expenseReport'])->name('reports.expenses');

    // Step 7: Audit Trail & Logs (Owner & Incharge)
    Route::middleware('role:owner,incharge')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/hr.php';
