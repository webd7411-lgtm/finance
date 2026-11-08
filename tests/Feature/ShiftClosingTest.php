<?php

namespace Tests\Feature;

use App\Models\Cashier;
use App\Models\Pump;
use App\Models\Product;
use App\Models\ShiftClosing;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShiftClosingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Cashier $cashier;
    protected Product $product;
    protected Pump $pump;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('test-password'),
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->cashier = Cashier::create([
            'name' => 'Ali Raza',
            'code' => 'CSH-01',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Super Petrol (RON 92)',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 265.00,
            'sale_price' => 280.00,
            'opening_stock' => 10000,
            'current_stock' => 10000,
            'status' => 'active',
        ]);

        $this->pump = Pump::create([
            'name' => 'Pump Machine 1 (Super Petrol)',
            'code' => 'PUMP-01',
            'product_id' => $this->product->id,
            'last_reading' => 12000.00,
            'status' => 'active',
        ]);
    }

    public function test_shift_closing_records_meter_readings_and_auto_calculates_liters_and_sale(): void
    {
        $today = now()->toDateString();

        $payload = [
            'date' => $today,
            'shift_type' => 'morning',
            'cashier_id' => $this->cashier->id,
            'pump_id' => $this->pump->id,
            'product_id' => $this->product->id,
            'start_reading' => '12000.00',
            'end_reading' => '12500.00', // 500 liters
            'total_liters' => '500.00',
            'fuel_rate' => '280.00',
            'total_sale' => '140000.00', // 500 * 280 = 140,000
            'expenses_amount' => '1000.00', // expected cash = 139,000
            'expenses_details' => 'Station maintenance & tea',
            'note_5000' => 27, // 135,000
            'note_1000' => 4,  // 4,000 -> Total Counted Cash = 139,000
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);

        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));

        $this->assertEquals(12000.00, (float) $closing->start_reading);
        $this->assertEquals(12500.00, (float) $closing->end_reading);
        $this->assertEquals(500.00, (float) $closing->total_liters);
        $this->assertEquals(280.00, (float) $closing->fuel_rate);
        $this->assertEquals(140000.00, (float) $closing->total_sale);
        $this->assertEquals(1000.00, (float) $closing->expenses_amount);
        $this->assertEquals(139000.00, (float) $closing->expected_cash);
        $this->assertEquals(0.00, (float) $closing->difference);

        // Verify Pump last_reading updated
        $this->assertEquals(12500.00, (float) $this->pump->fresh()->last_reading);

        // Verify Product stock decremented by 500 liters (10000 - 500 = 9500)
        $this->assertEquals(9500.00, (float) $this->product->fresh()->current_stock);
    }

    public function test_allows_multiple_shifts_closed_on_same_day(): void
    {
        $today = now()->toDateString();

        // 1st Shift
        $payload1 = [
            'date' => $today,
            'shift_type' => 'morning',
            'cashier_id' => $this->cashier->id,
            'pump_id' => $this->pump->id,
            'start_reading' => '1000',
            'end_reading' => '1100',
            'total_liters' => '100',
            'fuel_rate' => '280',
            'total_sale' => '28000',
            'note_5000' => 5,
            'note_1000' => 3, // 28,000 counted
        ];
        $res1 = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload1);
        $res1->assertSessionHasNoErrors();

        // 2nd Shift on the SAME DATE and SAME SHIFT TYPE is now allowed!
        $payload2 = [
            'date' => $today,
            'shift_type' => 'morning',
            'cashier_id' => $this->cashier->id,
            'pump_id' => $this->pump->id,
            'start_reading' => '1100',
            'end_reading' => '1200',
            'total_liters' => '100',
            'fuel_rate' => '280',
            'total_sale' => '28000',
            'note_5000' => 5,
            'note_1000' => 3, // 28,000 counted
        ];
        $res2 = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload2);
        $res2->assertSessionHasNoErrors();

        $this->assertEquals(2, ShiftClosing::whereDate('date', $today)->count());
    }

    public function test_shift_closing_records_account_payments_and_updates_account_balance(): void
    {
        $today = now()->toDateString();
        $account = Account::create([
            'name' => 'Meezan Bank Raast QR',
            'type' => 'bank',
            'account_number' => '123456789',
            'opening_balance' => 5000,
            'current_balance' => 5000,
            'status' => 'active',
        ]);

        $payload = [
            'date' => $today,
            'shift_type' => 'evening',
            'cashier_id' => $this->cashier->id,
            'start_reading' => '1000',
            'end_reading' => '1100',
            'total_liters' => '100',
            'fuel_rate' => '280',
            'total_sale' => '28000',
            'account_payments' => [
                [
                    'account_id' => $account->id,
                    'amount' => '8000',
                    'description' => 'Direct Raast QR transfer from fleet customer',
                ],
            ],
            'note_5000' => 4, // 20,000 cash counted + 8000 online = 28,000 total actual
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);

        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));

        $this->assertEquals(28000.00, (float) $closing->expected_cash);
        $this->assertEquals(28000.00, (float) $closing->total_actual_received);
        $this->assertEquals(0.00, (float) $closing->difference);
        $this->assertEquals(8000.00, (float) $closing->bank_amount);

        // Account balance incremented from 5000 to 13000
        $this->assertEquals(13000.00, (float) $account->fresh()->current_balance);

        $this->assertDatabaseHas('transactions', [
            'shift_closing_id' => $closing->id,
            'account_id' => $account->id,
            'amount' => 8000,
            'type' => 'payment_in',
        ]);
    }

    public function test_shift_closing_accepts_comma_formatted_readings_and_amounts(): void
    {
        $today = now()->toDateString();

        $payload = [
            'date' => $today,
            'shift_type' => 'morning',
            'cashier_id' => $this->cashier->id,
            'start_reading' => '15,000.00',
            'end_reading' => '15,250.00', // 250 Ltr
            'total_liters' => '250.00',
            'fuel_rate' => '280.00',
            'total_sale' => '70,000.00',
            'expenses_amount' => '2,000.00', // expected = 68,000
            'coins' => '500.00',
            'note_5000' => 13, // 65,000
            'note_1000' => 2,  // 2,000
            'note_500' => 1,   // 500 -> 65k + 2k + 500 + 500 coins = 68,000
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);

        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));

        $this->assertEquals(15000.00, (float) $closing->start_reading);
        $this->assertEquals(15250.00, (float) $closing->end_reading);
        $this->assertEquals(250.00, (float) $closing->total_liters);
        $this->assertEquals(70000.00, (float) $closing->total_sale);
        $this->assertEquals(2000.00, (float) $closing->expenses_amount);
        $this->assertEquals(68000.00, (float) $closing->expected_cash);
        $this->assertEquals(0.00, (float) $closing->difference);
    }
}
