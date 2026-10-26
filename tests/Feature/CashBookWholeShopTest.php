<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Models\Party;
use App\Models\ShiftClosing;
use App\Models\ShiftClosingPartyPayment;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CashBookWholeShopTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_cash_book_renders_account_dropdown(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/ledgers/cash-book');
        $response->assertOk();
        $response->assertSee('Account / Report Mode');
        $response->assertSee('Whole Shop (All Accounts &amp; Sales Report)', false);
    }

    public function test_cash_book_whole_shop_report_renders_net_sale_and_account_columns(): void
    {
        $user = User::first() ?? User::factory()->create();

        $response = $this->actingAs($user)->get('/ledgers/cash-book?account_id=whole_shop');
        $response->assertOk();
        $response->assertViewHas('isWholeShop', true);
        $response->assertViewHas('accounts');
        $response->assertSee('Net Sale');
        $response->assertSee('Voucher #');
        $response->assertSee('Party / Payee');
        $response->assertSee('Particulars / Details');
        $response->assertSee('In (+)');
        $response->assertSee('Out (-)');
        $response->assertSee('Running');
        $response->assertSee('Whole Shop Financial &amp; Liquidity Report', false);
    }

    public function test_shift_party_payments_render_as_payment_out_rows(): void
    {
        $user = User::first() ?? User::factory()->create();
        $cashAccount = Account::getCashAccount();
        $cashAccount->update(['opening_balance' => 10000]);

        $party = Party::create([
            'name' => 'Ahmed Supplier',
            'type' => 'supplier',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $category = ExpenseCategory::create([
            'name' => 'Shop Rent',
            'type' => 'expense',
        ]);

        $shift = ShiftClosing::create([
            'date' => Carbon::today()->toDateString(),
            'shift_type' => 'evening',
            'cashier_id' => $user->id,
            'total_invoices' => 20,
            'total_sale' => 50000,
            'returns_amount' => 5000,
            'expenses_amount' => 0,
            'total_counted_cash' => 43000,
            'jazzcash_amount' => 0,
            'bank_amount' => 0,
            'total_actual_received' => 43000,
            'expected_cash' => 43000,
            'difference' => 0,
            'status' => 'locked',
        ]);

        ShiftClosingPartyPayment::create([
            'shift_closing_id' => $shift->id,
            'party_id' => $party->id,
            'expense_category_id' => $category->id,
            'amount' => 2000,
            'details' => 'Shift cash payout to Ahmed',
        ]);

        // 1. Verify Whole Shop Mode
        $response = $this->actingAs($user)->get('/ledgers/cash-book?account_id=whole_shop&from_date=' . Carbon::today()->startOfMonth()->toDateString() . '&to_date=' . Carbon::today()->toDateString());
        $response->assertOk();
        $response->assertSee('Ahmed Supplier');
        $response->assertSee('Shift cash payout to Ahmed');
        $response->assertSee('Shop Rent');
        $response->assertSee('- 2,000.00');

        // 2. Verify Cash Account Single Register Mode
        $cashResponse = $this->actingAs($user)->get('/ledgers/cash-book?account_id=cash&from_date=' . Carbon::today()->startOfMonth()->toDateString() . '&to_date=' . Carbon::today()->toDateString());
        $cashResponse->assertOk();
        $cashResponse->assertSee('Ahmed Supplier');
        $cashResponse->assertSee('- 2,000.00');
    }

    public function test_shift_digital_account_collection_renders_in_same_shift_row_without_duplicate(): void
    {
        $user = User::first() ?? User::factory()->create();
        $cashAccount = Account::getCashAccount();
        $cashAccount->update(['opening_balance' => 50000]);

        $jazzAccount = Account::create([
            'name' => 'JazzCash Business',
            'type' => 'jazzcash',
            'opening_balance' => 10000,
            'current_balance' => 10500,
        ]);

        $shift = ShiftClosing::create([
            'date' => Carbon::today()->toDateString(),
            'shift_type' => 'evening',
            'cashier_id' => $user->id,
            'total_invoices' => 30,
            'total_sale' => 40000,
            'returns_amount' => 0,
            'expenses_amount' => 0,
            'total_counted_cash' => 39500,
            'jazzcash_amount' => 500,
            'bank_amount' => 0,
            'total_actual_received' => 40000,
            'expected_cash' => 40000,
            'difference' => 0,
            'status' => 'locked',
        ]);

        // Transaction created by shift closing for JazzCash collection
        Transaction::create([
            'date' => Carbon::today()->toDateString(),
            'type' => 'payment_in',
            'shift_closing_id' => $shift->id,
            'account_id' => $jazzAccount->id,
            'amount' => 500,
            'bill_no' => 'Shift #' . $shift->id,
            'description' => 'Shift closing collection (evening)',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/ledgers/cash-book?account_id=whole_shop&from_date=' . Carbon::today()->startOfMonth()->toDateString() . '&to_date=' . Carbon::today()->toDateString());
        $response->assertOk();

        $rows = $response->viewData('rows');
        // There should be only 1 row for this shift, combining both Cash and JazzCash inflows
        $shiftRows = collect($rows)->filter(fn($r) => ($r['shift_id'] ?? null) == $shift->id);
        $this->assertCount(1, $shiftRows);

        $shiftRow = $shiftRows->first();
        $this->assertEquals(39500, $shiftRow['accounts'][$cashAccount->id]['in']);
        $this->assertEquals(500, $shiftRow['accounts'][$jazzAccount->id]['in']);
    }

    public function test_shift_closing_statement_displays_closed_by_username(): void
    {
        $user = User::factory()->create(['name' => 'Tariq Mehmood']);

        $shift = ShiftClosing::create([
            'date' => Carbon::today()->toDateString(),
            'shift_type' => 'evening',
            'cashier_id' => $user->id,
            'closed_by' => $user->id,
            'total_invoices' => 10,
            'total_sale' => 10000,
            'returns_amount' => 0,
            'expenses_amount' => 0,
            'total_counted_cash' => 10000,
            'jazzcash_amount' => 0,
            'bank_amount' => 0,
            'total_actual_received' => 10000,
            'expected_cash' => 10000,
            'difference' => 0,
            'status' => 'locked',
        ]);

        $response = $this->actingAs($user)->get(route('shift-closings.show', $shift->id));
        $response->assertOk();
        $response->assertSee('Closed By:');
        $response->assertSee('Tariq Mehmood');
        $response->assertSee('<strong>Closed By:</strong> Tariq Mehmood', false);
    }
}
