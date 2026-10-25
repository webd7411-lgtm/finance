<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\ShiftClosing;
use App\Models\User;
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
        $response->assertSee('In (+)');
        $response->assertSee('Out (-)');
        $response->assertSee('Running');
        $response->assertSee('Whole Shop Financial &amp; Liquidity Report', false);
    }
}
