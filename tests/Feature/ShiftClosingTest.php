<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\ShiftClosing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShiftClosingTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_closing_counts_invoice_range_and_records_cash_party_payment(): void
    {
        $user = User::factory()->create(['password' => Hash::make('test-password')]);
        $party = Party::create([
            'name' => 'Test Supplier',
            'type' => 'supplier',
            'opening_balance' => 1000,
            'current_balance' => 1000,
        ]);

        $response = $this->actingAs($user)->post(route('shift-closings.store'), [
            'date' => '2026-10-04',
            'shift_type' => 'evening',
            'invoice_start' => 1201,
            'invoice_end' => 1250,
            'total_sale' => 1000,
            'returns_amount' => 50,
            'return_invoice_number' => 'RET-1250',
            'expenses_amount' => 50,
            'expenses_details' => 'Shop supplies',
            'note_500' => 1,
            'note_100' => 3,
            'party_payments' => [
                [
                    'party_id' => $party->id,
                    'amount' => 100,
                    'details' => 'Cash payment against balance',
                ],
            ],
        ]);

        $closing = ShiftClosing::firstOrFail();

        $response->assertRedirect(route('shift-closings.show', $closing));
        $this->assertSame(50, $closing->total_invoices);
        $this->assertSame('1201', (string) $closing->invoice_start);
        $this->assertSame('1250', (string) $closing->invoice_end);
        $this->assertSame('800.00', $closing->expected_cash);
        $this->assertSame(0.0, (float) $closing->difference);
        $this->assertSame('900.00', $party->fresh()->current_balance);
        $this->assertDatabaseHas('shift_closing_party_payments', [
            'shift_closing_id' => $closing->id,
            'party_id' => $party->id,
            'amount' => 100,
            'details' => 'Cash payment against balance',
        ]);
    }

    public function test_shift_closing_records_account_payments_and_updates_account_balance(): void
    {
        $user = User::factory()->create(['password' => Hash::make('test-password'), 'role' => 'owner']);
        $account = \App\Models\Account::create([
            'name' => 'Meezan Bank',
            'type' => 'bank',
            'account_number' => '123456789',
            'opening_balance' => 5000,
            'current_balance' => 5000,
        ]);

        $response = $this->actingAs($user)->post(route('shift-closings.store'), [
            'date' => '2026-10-04',
            'shift_type' => 'morning',
            'invoice_start' => 100,
            'invoice_end' => 110,
            'total_sale' => 2000,
            'returns_amount' => 0,
            'expenses_amount' => 0,
            'account_payments' => [
                [
                    'account_id' => $account->id,
                    'amount' => 500,
                    'description' => 'Customer QR Transfer',
                ],
            ],
            'note_500' => 3, // 1500 counted cash
        ]);

        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));

        // Total sale 2000 - returns 0 - expenses 0 - party 0 = expected cash 2000
        $this->assertSame('2000.00', $closing->expected_cash);
        $this->assertSame(0.0, (float) $closing->difference);
        $this->assertSame('500.00', $closing->fresh()->bank_amount);

        // Account balance updated from 5000 to 5500
        $this->assertSame('5500.00', $account->fresh()->current_balance);

        // Transaction created
        $this->assertDatabaseHas('transactions', [
            'shift_closing_id' => $closing->id,
            'account_id' => $account->id,
            'amount' => 500,
            'type' => 'payment_in',
        ]);

        // Test delete shifts closing reverses account balance
        $deleteResponse = $this->actingAs($user)->delete(route('shift-closings.destroy', $closing));
        $deleteResponse->assertRedirect(route('shift-closings.index'));
        $this->assertSame('5000.00', $account->fresh()->current_balance);
        $this->assertDatabaseMissing('transactions', [
            'shift_closing_id' => $closing->id,
        ]);
    }

    public function test_shift_closing_accepts_comma_formatted_amounts(): void
    {
        $user = User::factory()->create(['password' => Hash::make('test-password')]);
        $party = Party::create([
            'name' => 'Supplier XYZ',
            'type' => 'supplier',
            'opening_balance' => 10000,
            'current_balance' => 10000,
        ]);
        $account = \App\Models\Account::create([
            'name' => 'JazzCash Shop',
            'type' => 'jazzcash',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('shift-closings.store'), [
            'date' => '2026-10-04',
            'shift_type' => 'evening',
            'invoice_start' => 2001,
            'invoice_end' => 2050,
            'total_sale' => '50,000.00',
            'returns_amount' => '1,500.00',
            'expenses_amount' => '500.00',
            'coins' => '250.00',
            'note_5000' => 8, // 40,000
            'note_1000' => 4, // 4,000
            'party_payments' => [
                [
                    'party_id' => $party->id,
                    'amount' => '3,000.00',
                    'details' => 'Supplier invoice payment',
                ],
            ],
            'account_payments' => [
                [
                    'account_id' => $account->id,
                    'amount' => '1,000.00',
                    'description' => 'Direct Jazzcash receipt',
                ],
            ],
        ]);

        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));

        $this->assertSame('50000.00', $closing->total_sale);
        $this->assertSame('1500.00', $closing->returns_amount);
        $this->assertSame('500.00', $closing->expenses_amount);
        // expected cash = 50000 - 1500 - 500 - 3000 = 45000
        $this->assertSame('45000.00', $closing->expected_cash);
        $this->assertSame('7000.00', $party->fresh()->current_balance);
        $this->assertSame('1000.00', $account->fresh()->current_balance);
    }
}