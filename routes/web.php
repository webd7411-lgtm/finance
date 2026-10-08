<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\ExpenseCategoryController;
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
    // Step 1: Users & Roles Module (Owner & Incharge Access)
    Route::middleware('role:owner,incharge')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });

    // Step 2: Master Setup (Parties, Accounts, Categories)
    Route::resource('parties', PartyController::class);
    Route::resource('accounts', AccountController::class);
    Route::resource('expense-categories', ExpenseCategoryController::class)->except(['create', 'show', 'edit']);

    // Step 3: Shift Closing Module (Morning & Evening)
    Route::resource('shift-closings', ShiftClosingController::class)->except(['edit', 'update']);
    Route::post('shift-closings/{shiftClosing}/unlock', [ShiftClosingController::class, 'unlock'])->name('shift-closings.unlock');
    Route::post('shift-closings/{shiftClosing}/lock', [ShiftClosingController::class, 'lock'])->name('shift-closings.lock');

    // Step 4: Daily Vouchers (Payment In & Payment Out)
    Route::post('transactions/transfer', [TransactionController::class, 'transfer'])->name('transactions.transfer');
    Route::resource('transactions', TransactionController::class)->except(['edit', 'update', 'create']);

    // Step 5: Day Closing Module (Dono Shifts Merge & Auto Opening)
    Route::resource('day-closings', DayClosingController::class)->except(['edit', 'update', 'create']);
    Route::post('day-closings/{dayClosing}/reopen', [DayClosingController::class, 'reopen'])->name('day-closings.reopen');

    // Step 6: Ledgers & Reports Module
    Route::get('ledgers/party/{party?}', [LedgerController::class, 'partyLedger'])->name('ledgers.party');
    Route::get('ledgers/cash-book', [LedgerController::class, 'cashBook'])->name('ledgers.cash-book');
    Route::get('ledgers/bank-book', [LedgerController::class, 'bankBook'])->name('ledgers.bank-book');

    Route::get('reports/daily-closing', [ReportController::class, 'dailyClosingSummary'])->name('reports.daily-closing');
    Route::get('reports/total-sale-summary', [ReportController::class, 'totalSaleSummary'])->name('reports.total-sale-summary');
    Route::get('reports/variance', [ReportController::class, 'varianceAudit'])->name('reports.variance');
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
