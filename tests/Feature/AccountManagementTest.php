<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_only_created_accounts_show_in_kpi_cards(): void
    {
        // Initially, ensure only Cash in Hand exists
        Account::query()->delete();
        $cashAcc = Account::create([
            'name' => 'Cash in Hand',
            'type' => 'cash',
            'opening_balance' => 0.00,
            'current_balance' => 0.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('accounts.index'));
        $response->assertStatus(200);

        // Should see Cash in Hand and Total Liquid Balance
        $response->assertSee('Total Liquid Balance');
        $response->assertSee('Cash in Hand');

        // Should NOT see uncreated account cards: JazzCash / Wallets or generic Bank Accounts
        $response->assertDontSee('JazzCash / Wallets');
        $response->assertDontSee('Commercial bank balances');
    }

    public function test_newly_created_bank_and_wallet_accounts_render_their_own_boxes(): void
    {
        Account::query()->delete();
        Account::create([
            'name' => 'Cash in Hand',
            'type' => 'cash',
            'opening_balance' => 5000.00,
            'current_balance' => 5000.00,
            'status' => 'active',
        ]);

        Account::create([
            'name' => 'Meezan Bank - Current',
            'type' => 'bank',
            'account_number' => '0102030405',
            'opening_balance' => 50000.00,
            'current_balance' => 50000.00,
            'status' => 'active',
        ]);

        Account::create([
            'name' => 'JazzCash Merchant 0300',
            'type' => 'jazzcash',
            'account_number' => '03001234567',
            'opening_balance' => 12000.00,
            'current_balance' => 12000.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('accounts.index'));
        $response->assertStatus(200);

        // Shows specific account names on their square cards
        $response->assertSee('Meezan Bank - Current');
        $response->assertSee('JazzCash Merchant 0300');
        $response->assertSee('0102030405');
        $response->assertSee('03001234567');

        // Total should equal 5,000 + 50,000 + 12,000 = 67,000.00
        $response->assertSee('Rs. 67,000.00');
    }
}
