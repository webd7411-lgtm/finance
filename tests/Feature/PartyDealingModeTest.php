<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Party;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartyDealingModeTest extends TestCase
{
    use DatabaseMigrations;

    public function test_parties_can_be_created_with_credit_and_cash_checkboxes(): void
    {
        $user = User::factory()->create();

        // 1. Create a party with only Credit
        $response1 = $this->actingAs($user)->post(route('parties.store'), [
            'name' => 'Credit Supplier Inc',
            'type' => 'supplier',
            'dealing_mode_submitted' => '1',
            'is_credit' => '1',
            'opening_balance' => '500.00',
        ]);
        $response1->assertRedirect(route('parties.index'));
        $this->assertDatabaseHas('parties', [
            'name' => 'Credit Supplier Inc',
            'is_credit' => 1,
            'is_cash' => 0,
        ]);

        // 2. Create a party with only Cash
        $response2 = $this->actingAs($user)->post(route('parties.store'), [
            'name' => 'Cash Customer Shop',
            'type' => 'customer',
            'dealing_mode_submitted' => '1',
            'is_cash' => '1',
            'opening_balance' => '0',
        ]);
        $response2->assertRedirect(route('parties.index'));
        $this->assertDatabaseHas('parties', [
            'name' => 'Cash Customer Shop',
            'is_credit' => 0,
            'is_cash' => 1,
        ]);

        // 3. Create a party with both Credit and Cash
        $response3 = $this->actingAs($user)->post(route('parties.store'), [
            'name' => 'Both Modes Trader',
            'type' => 'trader',
            'dealing_mode_submitted' => '1',
            'is_credit' => '1',
            'is_cash' => '1',
            'opening_balance' => '1000.00',
        ]);
        $response3->assertRedirect(route('parties.index'));
        $this->assertDatabaseHas('parties', [
            'name' => 'Both Modes Trader',
            'is_credit' => 1,
            'is_cash' => 1,
        ]);
    }

    public function test_parties_filter_by_credit_and_cash(): void
    {
        $user = User::factory()->create();

        $creditParty = Party::create([
            'name' => 'Alpha Credit Corp',
            'type' => 'supplier',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $cashParty = Party::create([
            'name' => 'Beta Cash Mart',
            'type' => 'customer',
            'is_credit' => false,
            'is_cash' => true,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        // Filter by Credit
        $responseCredit = $this->actingAs($user)->get(route('parties.index', ['mode' => 'credit']));
        $responseCredit->assertOk();
        $responseCredit->assertSee('Alpha Credit Corp');
        $responseCredit->assertDontSee('Beta Cash Mart');

        // Filter by Cash
        $responseCash = $this->actingAs($user)->get(route('parties.index', ['mode' => 'cash']));
        $responseCash->assertOk();
        $responseCash->assertSee('Beta Cash Mart');
        $responseCash->assertDontSee('Alpha Credit Corp');
    }

    public function test_add_party_form_renders_credit_and_cash_checkboxes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('parties.create'));
        $response->assertOk();
        $response->assertSee('name="is_credit"', false);
        $response->assertSee('name="is_cash"', false);
        $response->assertSee('Dealing Mode', false);
    }

    public function test_party_requires_at_least_one_dealing_mode_when_submitted(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->from(route('parties.create'))->post(route('parties.store'), [
            'name' => 'No Mode Party',
            'type' => 'supplier',
            'dealing_mode_submitted' => '1',
            // neither is_credit nor is_cash checked
        ]);

        $response->assertRedirect(route('parties.create'));
        $response->assertSessionHasErrors(['dealing_mode']);
    }

    public function test_party_update_dealing_modes(): void
    {
        $user = User::factory()->create();

        $party = Party::create([
            'name' => 'Delta Traders',
            'type' => 'trader',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        // Switch to cash only
        $response = $this->actingAs($user)->put(route('parties.update', $party->id), [
            'name' => 'Delta Traders',
            'type' => 'trader',
            'dealing_mode_submitted' => '1',
            'is_cash' => '1',
        ]);

        $response->assertRedirect(route('parties.index'));
        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'is_credit' => 0,
            'is_cash' => 1,
        ]);
    }

    public function test_party_ledger_combined_credit_and_cash_modes(): void
    {
        $user = User::factory()->create();

        $creditParty = Party::create([
            'name' => 'Global Pharma Credit',
            'type' => 'supplier',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $cashParty = Party::create([
            'name' => 'Direct Walkin Cash',
            'type' => 'customer',
            'is_credit' => false,
            'is_cash' => true,
            'opening_balance' => 500,
            'current_balance' => 500,
        ]);

        // 1. Combined Credit Ledger
        $creditLedgerResponse = $this->actingAs($user)->get(route('ledgers.party', [
            'party_id' => 'all_credit',
        ]));
        $creditLedgerResponse->assertOk();
        $creditLedgerResponse->assertViewHas('ledgerScope', 'all_credit');
        $creditLedgerResponse->assertSee('All Credit Parties');
        $creditParties = $creditLedgerResponse->viewData('selectedParties');
        $this->assertTrue($creditParties->contains('id', $creditParty->id));
        $this->assertFalse($creditParties->contains('id', $cashParty->id));

        // 2. Combined Cash Ledger
        $cashLedgerResponse = $this->actingAs($user)->get(route('ledgers.party', [
            'party_id' => 'all_cash',
        ]));
        $cashLedgerResponse->assertOk();
        $cashLedgerResponse->assertViewHas('ledgerScope', 'all_cash');
        $cashLedgerResponse->assertSee('All Cash Parties');
        $cashParties = $cashLedgerResponse->viewData('selectedParties');
        $this->assertTrue($cashParties->contains('id', $cashParty->id));
        $this->assertFalse($cashParties->contains('id', $creditParty->id));
    }

    public function test_party_ledger_combined_credit_scope_groups_transactions_by_party_consecutively(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $alpha = Party::create([
            'name' => 'Alpha Distributors',
            'type' => 'supplier',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $beta = Party::create([
            'name' => 'Beta Pharmaceuticals',
            'type' => 'supplier',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        // Create interleaved transactions by date:
        // Alpha: Oct 1
        Transaction::create([
            'date' => '2026-10-01',
            'party_id' => $alpha->id,
            'account_id' => $account->id,
            'type' => 'payment_out',
            'amount' => 100,
            'description' => 'Alpha Tx 1',
            'created_by' => $user->id,
        ]);

        // Beta: Oct 2
        Transaction::create([
            'date' => '2026-10-02',
            'party_id' => $beta->id,
            'account_id' => $account->id,
            'type' => 'payment_out',
            'amount' => 200,
            'description' => 'Beta Tx 1',
            'created_by' => $user->id,
        ]);

        // Alpha: Oct 3
        Transaction::create([
            'date' => '2026-10-03',
            'party_id' => $alpha->id,
            'account_id' => $account->id,
            'type' => 'payment_out',
            'amount' => 300,
            'description' => 'Alpha Tx 2',
            'created_by' => $user->id,
        ]);

        // Beta: Oct 4
        Transaction::create([
            'date' => '2026-10-04',
            'party_id' => $beta->id,
            'account_id' => $account->id,
            'type' => 'payment_out',
            'amount' => 400,
            'description' => 'Beta Tx 2',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('ledgers.party', [
            'party_id' => 'all_credit',
            'from_date' => '2026-10-01',
            'to_date' => '2026-10-10',
        ]));

        $response->assertOk();
        $transactions = $response->viewData('transactions');
        $this->assertCount(4, $transactions);

        // Verify that all Alpha transactions appear together first, then all Beta transactions appear together:
        $partyNames = $transactions->pluck('party_name')->all();
        $this->assertSame([
            'Alpha Distributors',
            'Alpha Distributors',
            'Beta Pharmaceuticals',
            'Beta Pharmaceuticals',
        ], $partyNames);

        // Within Alpha, dates should be Oct 1, then Oct 3
        $alphaDates = $transactions->where('party_name', 'Alpha Distributors')->pluck('date')->values()->all();
        $this->assertSame(['2026-10-01', '2026-10-03'], $alphaDates);

        // Within Beta, dates should be Oct 2, then Oct 4
        $betaDates = $transactions->where('party_name', 'Beta Pharmaceuticals')->pluck('date')->values()->all();
        $this->assertSame(['2026-10-02', '2026-10-04'], $betaDates);
    }

    public function test_party_ledger_multi_select_renders_separate_groups_and_grand_totals(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'name' => 'Main Cash Drawer',
            'type' => 'cash',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $party1 = Party::create([
            'name' => 'Party One Supplier',
            'type' => 'supplier',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $party2 = Party::create([
            'name' => 'Party Two Customer',
            'type' => 'customer',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 500,
            'current_balance' => 500,
        ]);

        $party3 = Party::create([
            'name' => 'Party Three Not Selected',
            'type' => 'supplier',
            'is_credit' => true,
            'is_cash' => false,
            'opening_balance' => 300,
            'current_balance' => 300,
        ]);

        // Transaction for Party 1
        Transaction::create([
            'date' => '2026-10-05',
            'party_id' => $party1->id,
            'account_id' => $account->id,
            'type' => 'payment_out',
            'amount' => 200,
            'description' => 'Supplier Payment',
            'created_by' => $user->id,
        ]);

        // Transaction for Party 2
        Transaction::create([
            'date' => '2026-10-06',
            'party_id' => $party2->id,
            'account_id' => $account->id,
            'type' => 'payment_in',
            'amount' => 150,
            'description' => 'Customer Cash Receipt',
            'created_by' => $user->id,
        ]);

        // Call with multi-select: party1 and party2
        $response = $this->actingAs($user)->get(route('ledgers.party', [
            'party_ids' => [$party1->id, $party2->id],
            'from_date' => '2026-10-01',
            'to_date' => '2026-10-10',
        ]));

        $response->assertOk();
        $response->assertViewHas('ledgerScope', 'multiple');
        $partyGroups = $response->viewData('partyGroups');
        $this->assertCount(2, $partyGroups);

        $group1 = collect($partyGroups)->firstWhere('party.name', 'Party One Supplier');
        $group2 = collect($partyGroups)->firstWhere('party.name', 'Party Two Customer');

        $this->assertNotNull($group1);
        $this->assertNotNull($group2);

        // Group 1 assertions (Supplier: 1000 opening - 200 payment_out = 800 closing)
        $this->assertSame('Party One Supplier', $group1->party->name);
        $this->assertEquals(1000, $group1->opening_balance);
        $this->assertEquals(200, $group1->total_debit);
        $this->assertEquals(800, $group1->closing_balance);

        // Group 2 assertions (Customer: 500 opening - 150 payment_in = 350 closing)
        $this->assertSame('Party Two Customer', $group2->party->name);
        $this->assertEquals(500, $group2->opening_balance);
        $this->assertEquals(150, $group2->total_credit);
        $this->assertEquals(350, $group2->closing_balance);

        // Grand totals assertions across selected parties
        $this->assertEquals(1500, $response->viewData('openingBalance'));
        $this->assertEquals(200, $response->viewData('totalDebit'));
        $this->assertEquals(150, $response->viewData('totalCredit'));
        $this->assertEquals(1150, $response->viewData('closingBalance'));

        // Group check
        $groupPartyNames = collect($partyGroups)->pluck('party.name')->all();
        $this->assertContains('Party One Supplier', $groupPartyNames);
        $this->assertContains('Party Two Customer', $groupPartyNames);
        $this->assertNotContains('Party Three Not Selected', $groupPartyNames);

        // HTML checks
        $response->assertSee('Party One Supplier');
        $response->assertSee('Party Two Customer');
        $response->assertSee('Party-Wise Statements (2 Parties)');
    }
}
