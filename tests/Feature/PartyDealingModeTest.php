<?php

namespace Tests\Feature;

use App\Models\Party;
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
}
