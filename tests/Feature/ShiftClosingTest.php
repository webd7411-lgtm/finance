<?php

namespace Tests\Feature;

use App\Models\Cashier;
use App\Models\DayClosing;
use App\Models\FuelRateRevision;
use App\Models\Pump;
use App\Models\PumpMeterReading;
use App\Models\Product;
use App\Models\ShiftClosing;
use App\Models\ShiftClosingNozzle;
use App\Models\Account;
use App\Models\Tank;
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
    protected Tank $tank;
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

        $this->tank = Tank::create([
            'product_id' => $this->product->id,
            'tank_number' => 'TK-01',
            'name' => 'Underground Petrol Tank #1',
            'tank_type' => 'underground',
            'capacity' => 25000.00,
            'opening_stock' => 10000.00,
            'current_stock' => 10000.00,
            'dead_stock' => 500.00,
            'status' => 'active',
        ]);

        $this->pump = Pump::create([
            'name' => 'Pump Machine 1 (Super Petrol)',
            'code' => 'PUMP-01',
            'product_id' => $this->product->id,
            'tank_id' => $this->tank->id,
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

    public function test_midnight_rate_split_by_post_midnight_liters_calculates_exact_old_and_new_amounts(): void
    {
        // Exact user scenario:
        // Total 24-hr shift sale = 100 Ltr (Start 650 -> End 750)
        // Post-12 AM (12 AM - 9 AM) = 30 Ltr @ new rate 319.00 = Rs. 9,570.00
        // Remaining (Pre-12 AM) = 70 Ltr @ old rate 358.00 = Rs. 25,060.00
        // Total = Rs. 34,630.00
        $today = now()->toDateString();

        $bankAccount = Account::create([
            'name' => 'HBL Fuel Station',
            'type' => 'bank',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);

        $payload = [
            'date' => $today,
            'shift_type' => '24_hours',
            'cashier_id' => $this->cashier->id,
            'nozzles' => [
                $this->pump->id => [
                    'pump_id' => $this->pump->id,
                    'tank_id' => $this->tank->id,
                    'product_id' => $this->product->id,
                    'nozzle_name' => $this->pump->name,
                    'start_reading' => '650.00',
                    'end_reading' => '750.00',
                    'testing_liters' => '0.00',
                    'fuel_rate' => '319.00',
                    'has_rate_change' => '1',
                    'split_mode' => 'liters',
                    'new_rate_liters' => '30.00',
                    'old_fuel_rate' => '358.00',
                    'new_fuel_rate' => '319.00',
                ],
            ],
            'account_payments' => [
                [
                    'account_id' => $bankAccount->id,
                    'amount' => '4630.00',
                    'description' => 'Card POS collection',
                ],
            ],
            'note_5000' => 6, // 30,000 cash + 4,630 bank = 34,630
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);

        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));
        $response->assertSessionHasNoErrors();

        $this->assertTrue((bool) $closing->has_rate_change);
        $this->assertEquals(100.00, (float) $closing->total_liters);
        $this->assertEquals(34630.00, (float) $closing->total_sale);
        $this->assertEquals(-1170.00, (float) $closing->rate_difference_amount); // 30 * (319 - 358)
        $this->assertEquals(0.00, (float) $closing->difference);

        $nozzle = ShiftClosingNozzle::where('shift_closing_id', $closing->id)->firstOrFail();
        $this->assertTrue((bool) $nozzle->has_rate_change);
        $this->assertEquals(100.00, (float) $nozzle->sold_liters);
        $this->assertEquals(70.00, (float) $nozzle->old_rate_liters);
        $this->assertEquals(30.00, (float) $nozzle->new_rate_liters);
        $this->assertEquals(720.00, (float) $nozzle->midnight_reading); // auto-derived 650 + 70
        $this->assertEquals(25060.00, (float) $nozzle->old_rate_subtotal);
        $this->assertEquals(9570.00, (float) $nozzle->new_rate_subtotal);
        $this->assertEquals(34630.00, (float) $nozzle->subtotal);

        // Tank stock, product stock, pump last reading, and PumpMeterReading log all synced
        $this->assertEquals(9900.00, (float) $this->tank->fresh()->current_stock);
        $this->assertEquals(9900.00, (float) $this->product->fresh()->current_stock);
        $this->assertEquals(750.00, (float) $this->pump->fresh()->last_reading);

        $meterLog = PumpMeterReading::where('shift_closing_id', $closing->id)->firstOrFail();
        $this->assertEquals(100.00, (float) $meterLog->volume_dispensed);
        $this->assertEquals(34630.00, (float) $meterLog->sales_amount);
        $this->assertStringContainsString('Dispensed: 100L', $meterLog->remarks);

        // DayClosing auto-synced digital bank collection from 24_hours shift
        $dayClosing = DayClosing::whereDate('date', $today)->firstOrFail();
        $this->assertEquals(30000.00, (float) $dayClosing->total_cash_in);
        $this->assertEquals(4630.00, (float) $dayClosing->bank_in);
    }

    public function test_midnight_rate_split_accounts_for_testing_liters_without_stock_mismatch(): void
    {
        $today = now()->toDateString();

        // Start 650 -> End 755 (105 gross), Testing = 5 -> Net Sold = 100 Ltr
        // Post-12 AM = 30 Ltr @ 314, Remaining = 70 Ltr @ 319 -> 22,330 + 9,420 = 31,750
        $payload = [
            'date' => $today,
            'shift_type' => '24_hours',
            'cashier_id' => $this->cashier->id,
            'nozzles' => [
                $this->pump->id => [
                    'pump_id' => $this->pump->id,
                    'tank_id' => $this->tank->id,
                    'product_id' => $this->product->id,
                    'start_reading' => '650.00',
                    'end_reading' => '755.00',
                    'testing_liters' => '5.00',
                    'fuel_rate' => '314.00',
                    'has_rate_change' => '1',
                    'split_mode' => 'liters',
                    'new_rate_liters' => '30.00',
                    'old_fuel_rate' => '319.00',
                    'new_fuel_rate' => '314.00',
                ],
            ],
            'note_5000' => 6,
            'note_1000' => 1,
            'note_500' => 1,
            'note_100' => 2,
            'note_50' => 1, // 31,750
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);
        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));
        $response->assertSessionHasNoErrors();

        $nozzle = $closing->nozzles()->firstOrFail();

        $this->assertEquals(105.00, (float) $nozzle->meter_liters);
        $this->assertEquals(5.00, (float) $nozzle->testing_liters);
        $this->assertEquals(100.00, (float) $nozzle->sold_liters);
        $this->assertEquals(70.00, (float) $nozzle->old_rate_liters);
        $this->assertEquals(30.00, (float) $nozzle->new_rate_liters);
        $this->assertEquals(31750.00, (float) $nozzle->subtotal);
        $this->assertEquals(9900.00, (float) $this->tank->fresh()->current_stock);
    }

    public function test_midnight_rate_toggle_on_with_empty_post_midnight_liters_does_not_inflate_volume(): void
    {
        // Regression test for the video bug where empty midnight reading caused End (750) to be charged
        $today = now()->toDateString();

        $payload = [
            'date' => $today,
            'shift_type' => '24_hours',
            'cashier_id' => $this->cashier->id,
            'nozzles' => [
                $this->pump->id => [
                    'pump_id' => $this->pump->id,
                    'tank_id' => $this->tank->id,
                    'product_id' => $this->product->id,
                    'start_reading' => '650.00',
                    'end_reading' => '750.00',
                    'testing_liters' => '0.00',
                    'fuel_rate' => '314.00',
                    'has_rate_change' => '1',
                    'split_mode' => 'liters',
                    'new_rate_liters' => '',
                    'midnight_reading' => '',
                    'old_fuel_rate' => '319.00',
                    'new_fuel_rate' => '314.00',
                ],
            ],
            'note_5000' => 6,
            'note_1000' => 1,
            'note_500' => 1,
            'note_100' => 4, // 31,900 = 100 * 319
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);
        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));
        $response->assertSessionHasNoErrors();

        $nozzle = ShiftClosingNozzle::firstOrFail();
        $this->assertEquals(100.00, (float) $nozzle->sold_liters);
        $this->assertEquals(100.00, (float) $nozzle->old_rate_liters);
        $this->assertEquals(0.00, (float) $nozzle->new_rate_liters);
        $this->assertEquals(31900.00, (float) $nozzle->subtotal);
    }

    public function test_midnight_reading_mode_with_testing_liters_keeps_total_net_sold_consistent(): void
    {
        $today = now()->toDateString();

        // Start 650 -> End 755 (105 gross), Test = 5 (Net Sold = 100)
        // Midnight reading = 753 (postMeter = 2 < test 5) -> new = 0, old = 100
        $payload = [
            'date' => $today,
            'shift_type' => '24_hours',
            'cashier_id' => $this->cashier->id,
            'nozzles' => [
                $this->pump->id => [
                    'pump_id' => $this->pump->id,
                    'tank_id' => $this->tank->id,
                    'product_id' => $this->product->id,
                    'start_reading' => '650.00',
                    'end_reading' => '755.00',
                    'testing_liters' => '5.00',
                    'fuel_rate' => '314.00',
                    'has_rate_change' => '1',
                    'split_mode' => 'reading',
                    'midnight_reading' => '753.00',
                    'old_fuel_rate' => '319.00',
                    'new_fuel_rate' => '314.00',
                ],
            ],
            'note_5000' => 6,
            'note_1000' => 1,
            'note_500' => 1,
            'note_100' => 4, // 31,900
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);
        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));
        $response->assertSessionHasNoErrors();

        $nozzle = ShiftClosingNozzle::firstOrFail();
        $this->assertEquals(100.00, (float) $nozzle->sold_liters);
        $this->assertEquals(100.00, (float) $nozzle->old_rate_liters);
        $this->assertEquals(0.00, (float) $nozzle->new_rate_liters);
        $this->assertEquals(31900.00, (float) $nozzle->subtotal);
    }

    public function test_midnight_rate_split_rejects_post_midnight_liters_exceeding_net_sold(): void
    {
        $today = now()->toDateString();

        $payload = [
            'date' => $today,
            'shift_type' => '24_hours',
            'cashier_id' => $this->cashier->id,
            'nozzles' => [
                $this->pump->id => [
                    'pump_id' => $this->pump->id,
                    'tank_id' => $this->tank->id,
                    'product_id' => $this->product->id,
                    'start_reading' => '650.00',
                    'end_reading' => '750.00', // net sold = 100
                    'testing_liters' => '0.00',
                    'fuel_rate' => '314.00',
                    'has_rate_change' => '1',
                    'split_mode' => 'liters',
                    'new_rate_liters' => '120.00', // invalid > 100
                    'old_fuel_rate' => '319.00',
                    'new_fuel_rate' => '314.00',
                ],
            ],
            'note_5000' => 10,
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);
        $response->assertSessionHasErrors('nozzles');
        $this->assertEquals(0, ShiftClosing::count());
        $this->assertEquals(10000.00, (float) $this->tank->fresh()->current_stock);
    }

    public function test_create_view_prefills_old_rate_from_active_rate_revision_and_show_view_renders_split(): void
    {
        $this->product->update(['sale_price' => 319.00]);

        FuelRateRevision::create([
            'product_id' => $this->product->id,
            'affected_tank_id' => $this->tank->id,
            'effective_date' => now()->toDateString(),
            'effective_time' => '00:00:00',
            'old_sale_rate' => 358.00,
            'new_sale_rate' => 319.00,
            'rate_difference' => -39.00,
            'stock_at_revision' => 10000.00,
            'stock_gain_loss_amount' => -390000.00,
            'created_by' => $this->user->id,
        ]);

        $createRes = $this->actingAs($this->user)->get(route('shift-closings.create'));
        $createRes->assertStatus(200);
        $createRes->assertSee('Post-12 AM Sold (12 AM - 9 AM)');
        $createRes->assertSee('value="358.00"', false);
        $createRes->assertSee('value="319.00"', false);

        // Save a split shift and verify show & delete reversal
        $today = now()->toDateString();
        $storeRes = $this->actingAs($this->user)->post(route('shift-closings.store'), [
            'date' => $today,
            'shift_type' => '24_hours',
            'cashier_id' => $this->cashier->id,
            'nozzles' => [
                $this->pump->id => [
                    'pump_id' => $this->pump->id,
                    'tank_id' => $this->tank->id,
                    'product_id' => $this->product->id,
                    'start_reading' => '650.00',
                    'end_reading' => '750.00',
                    'testing_liters' => '0.00',
                    'fuel_rate' => '319.00',
                    'has_rate_change' => '1',
                    'split_mode' => 'liters',
                    'new_rate_liters' => '30.00',
                    'old_fuel_rate' => '358.00',
                    'new_fuel_rate' => '319.00',
                ],
            ],
            'note_5000' => 6,
            'note_1000' => 4,
            'note_500' => 1,
            'note_100' => 1,
            'note_20' => 1,
            'note_10' => 1, // 34,630
        ]);
        $closing = ShiftClosing::firstOrFail();
        $storeRes->assertRedirect(route('shift-closings.show', $closing));
        $storeRes->assertSessionHasNoErrors();

        $showRes = $this->actingAs($this->user)->get(route('shift-closings.show', $closing));
        $showRes->assertStatus(200);
        $showRes->assertSee('12 AM Midnight Split');
        $showRes->assertSee('Pre: 70.00L | Post: 30.00L');

        // Delete shift closing and verify full tank/product/pump reversal
        $delRes = $this->actingAs($this->user)->delete(route('shift-closings.destroy', $closing));
        $delRes->assertRedirect(route('shift-closings.index'));
        $this->assertEquals(10000.00, (float) $this->tank->fresh()->current_stock);
        $this->assertEquals(10000.00, (float) $this->product->fresh()->current_stock);
        $this->assertEquals(650.00, (float) $this->pump->fresh()->last_reading);
    }

    public function test_multi_nozzle_shift_with_mixed_rate_change_and_normal_nozzle_reconciles_accurately(): void
    {
        $diesel = Product::create([
            'name' => 'High Speed Diesel (HSD)',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 270.00,
            'sale_price' => 357.00,
            'opening_stock' => 20000,
            'current_stock' => 20000,
            'status' => 'active',
        ]);

        $dieselTank = Tank::create([
            'product_id' => $diesel->id,
            'tank_number' => 'TK-02',
            'name' => 'Underground Diesel Tank #2',
            'tank_type' => 'underground',
            'capacity' => 50000.00,
            'opening_stock' => 20000.00,
            'current_stock' => 20000.00,
            'dead_stock' => 500.00,
            'status' => 'active',
        ]);

        $dieselPump = Pump::create([
            'name' => 'Pump Machine 2 (HSD)',
            'code' => 'PUMP-02',
            'product_id' => $diesel->id,
            'tank_id' => $dieselTank->id,
            'last_reading' => 5000.00,
            'status' => 'active',
        ]);

        $today = now()->toDateString();

        // Petrol nozzle: 100 Ltr total -> 30 Ltr post-12 AM @ 319 (9,570) + 70 Ltr pre-12 AM @ 358 (25,060) = 34,630
        // Diesel nozzle: 50 Ltr total (no rate change) @ 357 = 17,850
        // Total fuel liters = 150 Ltr, Total fuel sale = 52,480
        // Counter expense = 480 -> Expected cash = 52,000
        $payload = [
            'date' => $today,
            'shift_type' => '24_hours',
            'cashier_id' => $this->cashier->id,
            'nozzles' => [
                $this->pump->id => [
                    'pump_id' => $this->pump->id,
                    'tank_id' => $this->tank->id,
                    'product_id' => $this->product->id,
                    'start_reading' => '650.00',
                    'end_reading' => '750.00',
                    'testing_liters' => '0.00',
                    'fuel_rate' => '319.00',
                    'has_rate_change' => '1',
                    'split_mode' => 'liters',
                    'new_rate_liters' => '30.00',
                    'old_fuel_rate' => '358.00',
                    'new_fuel_rate' => '319.00',
                ],
                $dieselPump->id => [
                    'pump_id' => $dieselPump->id,
                    'tank_id' => $dieselTank->id,
                    'product_id' => $diesel->id,
                    'start_reading' => '5000.00',
                    'end_reading' => '5050.00',
                    'testing_liters' => '0.00',
                    'fuel_rate' => '357.00',
                ],
            ],
            'counter_expenses' => [
                [
                    'category_name' => 'Tea & Refreshment',
                    'details' => 'Staff tea',
                    'amount' => '480.00',
                ],
            ],
            'note_5000' => 10,
            'note_1000' => 2, // 52,000
        ];

        $response = $this->actingAs($this->user)->post(route('shift-closings.store'), $payload);
        $closing = ShiftClosing::firstOrFail();
        $response->assertRedirect(route('shift-closings.show', $closing));

        $this->assertEquals(150.00, (float) $closing->total_liters);
        $this->assertEquals(52480.00, (float) $closing->total_sale);
        $this->assertEquals(480.00, (float) $closing->expenses_amount);
        $this->assertEquals(52000.00, (float) $closing->expected_cash);
        $this->assertEquals(0.00, (float) $closing->difference);

        $this->assertEquals(9900.00, (float) $this->tank->fresh()->current_stock);
        $this->assertEquals(19950.00, (float) $dieselTank->fresh()->current_stock);
        $this->assertEquals(2, ShiftClosingNozzle::where('shift_closing_id', $closing->id)->count());
    }
}
