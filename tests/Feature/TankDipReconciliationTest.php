<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\Product;
use App\Models\Pump;
use App\Models\Purchase;
use App\Models\StationLocation;
use App\Models\StationSetting;
use App\Models\Tank;
use App\Models\TankDipReading;
use App\Models\TankStockAdjustment;
use App\Models\User;
use App\Services\TankReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TankDipReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $cashier;
    protected StationLocation $location;
    protected Product $petrol;
    protected Tank $tank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role'   => 'owner',
            'status' => 'active',
        ]);

        $this->cashier = User::factory()->create([
            'role'   => 'cashier',
            'status' => 'active',
        ]);

        $this->location = StationLocation::create([
            'name'       => 'Main Station',
            'code'       => 'MAIN-01',
            'is_default' => true,
            'status'     => 'active',
        ]);

        $this->petrol = Product::create([
            'name'           => 'Super Petrol (RON 92)',
            'code'           => 'PET-92',
            'category'       => 'fuel',
            'unit'           => 'liter',
            'is_fuel'        => true,
            'purchase_price' => 260.00,
            'sale_price'     => 275.00,
            'opening_stock'  => 10000.00,
            'current_stock'  => 10000.00,
            'status'         => 'active',
        ]);

        $this->tank = Tank::create([
            'location_id'           => $this->location->id,
            'product_id'            => $this->petrol->id,
            'tank_number'           => 'TK-01',
            'name'                  => 'Underground Tank #1 (Petrol)',
            'tank_type'             => 'underground',
            'capacity'              => 25000.00,
            'opening_stock'         => 10000.00,
            'current_stock'         => 10000.00,
            'dead_stock'            => 500.00,
            'current_dip_reading'   => 1200.00,
            'use_default_tolerance' => true,
            'status'                => 'active',
        ]);
    }

    public function test_normal_short_and_excess_dip_fluctuations_log_without_altering_book_stock(): void
    {
        // Day 1: -10 L short (within default tolerance max(25L, 0.5%=50L) = 50L)
        $res1 = $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
            'reading_date'    => now()->subDay()->toDateString(),
            'reading_time'    => '08:00',
            'reading_context' => 'morning_opening',
            'dip_cm'          => 1198,
            'measured_liters' => 9990.00,
            'notes'           => 'Cool morning contraction',
        ]);
        $res1->assertRedirect();

        $this->tank->refresh();
        $this->assertEquals(10000.00, (float) $this->tank->current_stock);
        $this->assertEquals(-10.00, (float) $this->tank->last_variance_liters);
        $this->assertEquals('within_tolerance', $this->tank->last_variance_status);

        // Day 2: +10 L excess (thermal expansion) -> Period delta should be +20 L (-10 -> +10)
        $res2 = $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
            'reading_date'    => now()->toDateString(),
            'reading_time'    => '14:30',
            'reading_context' => 'daily_routine',
            'dip_cm'          => 1202,
            'measured_liters' => 10010.00,
            'temperature_c'   => 34.5,
            'notes'           => 'Afternoon thermal expansion',
        ]);
        $res2->assertRedirect();

        $this->tank->refresh();
        $this->assertEquals(10000.00, (float) $this->tank->current_stock);
        $this->assertEquals(10.00, (float) $this->tank->last_variance_liters);
        $this->assertEquals('within_tolerance', $this->tank->last_variance_status);

        $latestDip = TankDipReading::latest('id')->first();
        $this->assertEquals(20.00, (float) $latestDip->period_variance_liters);
        $this->assertFalse($latestDip->is_reconciled);
    }

    public function test_custom_tank_tolerance_and_consecutive_shortage_trend_detection(): void
    {
        // Set strict fixed tolerance on tank: warn 15 L, critical 30 L
        $this->tank->update([
            'use_default_tolerance'      => false,
            'tolerance_mode'             => 'fixed_liters',
            'warning_tolerance_liters'   => 15.00,
            'critical_tolerance_liters'  => 30.00,
            'warning_tolerance_percent'  => 0.50,
            'critical_tolerance_percent' => 1.00,
        ]);

        // 3 consecutive negative dips: -10 (normal), -20 (warning), -40 (critical)
        foreach ([9990, 9980, 9960] as $idx => $measured) {
            $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
                'reading_date'    => now()->subDays(2 - $idx)->toDateString(),
                'reading_context' => 'daily_routine',
                'dip_cm'          => 1190 - $idx,
                'measured_liters' => $measured,
            ])->assertRedirect();
        }

        $dips = TankDipReading::where('tank_id', $this->tank->id)->orderBy('id')->get();
        $this->assertEquals('within_tolerance', $dips[0]->variance_status);
        $this->assertEquals('warning', $dips[1]->variance_status);
        $this->assertEquals('critical', $dips[2]->variance_status);

        $service = app(TankReconciliationService::class);
        $trend = $service->detectConsecutiveShortageTrend($this->tank);
        $this->assertTrue($trend['has_trend']);
        $this->assertEquals(3, $trend['streak']);
    }

    public function test_dip_calibration_chart_upload_and_linear_interpolation(): void
    {
        $this->actingAs($this->owner)->post(route('tanks.dip-chart.save', $this->tank), [
            'bulk_csv' => "0, 0\n1000, 10000\n2000, 20000",
            'replace_existing' => '1',
        ])->assertRedirect();

        // Lookup midpoint 1450mm -> 14500.00 L
        $lookup = $this->actingAs($this->owner)->getJson(route('tanks.lookup-dip', [
            'tank'   => $this->tank->id,
            'dip_mm' => 1450,
        ]));

        $lookup->assertOk()->assertJson([
            'has_chart'     => true,
            'liters'        => 14500.00,
            'matched_exact' => false,
            'out_of_range'  => false,
        ]);

        // Record dip without manually entering measured_liters
        $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
            'reading_date'    => now()->toDateString(),
            'reading_context' => 'daily_routine',
            'dip_cm'          => 998, // interpolates to 9980 L
        ])->assertRedirect();

        $dip = TankDipReading::latest('id')->first();
        $this->assertEquals(9980.00, (float) $dip->measured_liters);
        $this->assertTrue($dip->is_chart_calculated);
        $this->assertEquals(-20.00, (float) $dip->variance_liters);
    }

    public function test_owner_reconciliation_syncs_tank_product_and_ledger_while_blocking_cashier(): void
    {
        $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
            'reading_date'    => now()->toDateString(),
            'reading_context' => 'audit_reconciliation',
            'dip_cm'          => 1195,
            'measured_liters' => 9970.00, // -30 L shortage
        ])->assertRedirect();

        $dip = TankDipReading::latest('id')->first();

        // Cashier must be forbidden
        $this->actingAs($this->cashier)->post(route('tanks.dip-readings.reconcile', [$this->tank, $dip]), [
            'reason_category' => 'evaporation_loss',
        ])->assertStatus(403);

        // Owner reconciles
        $this->actingAs($this->owner)->post(route('tanks.dip-readings.reconcile', [$this->tank, $dip]), [
            'reason_category' => 'evaporation_loss',
            'notes'           => 'Monthly verified evaporation shrinkage',
        ])->assertRedirect();

        $this->tank->refresh();
        $this->petrol->refresh();
        $dip->refresh();

        $this->assertEquals(9970.00, (float) $this->tank->current_stock);
        $this->assertEquals(9970.00, (float) $this->petrol->current_stock);
        $this->assertEquals(0.00, (float) $this->tank->last_variance_liters);
        $this->assertEquals('balanced', $this->tank->last_variance_status);
        $this->assertTrue($dip->is_reconciled);

        $this->assertDatabaseHas('tank_stock_adjustments', [
            'tank_id'           => $this->tank->id,
            'adjustment_type'   => 'loss',
            'book_stock_before' => 10000.00,
            'adjustment_liters' => -30.00,
            'book_stock_after'  => 9970.00,
            'financial_impact'  => -7800.00, // -30 * 260
        ]);

        // Verify tank detail ledger displays the adjustment
        $this->actingAs($this->owner)->get(route('tanks.show', $this->tank))
            ->assertOk()
            ->assertSee('Dip Reconciliation');
    }

    public function test_client_can_configure_auto_reconcile_within_tolerance_policy(): void
    {
        $this->actingAs($this->owner)->post(route('tanks.settings.update'), [
            'dip_unit'                   => 'mm',
            'tolerance_mode'             => 'fixed_liters',
            'warning_liters'             => 25,
            'critical_liters'            => 50,
            'warning_percent'            => 0.50,
            'critical_percent'           => 1.00,
            'consecutive_shortage_alert' => 3,
            'reconciliation_mode'        => 'auto_within_tolerance',
            'bowser_dip_mode'            => 'optional',
            'show_variance_on_dashboard' => 1,
        ])->assertRedirect(route('tanks.settings'));

        // -15 L is within 25 L -> should auto-reconcile
        $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
            'reading_date'    => now()->toDateString(),
            'reading_context' => 'daily_routine',
            'dip_cm'          => 1197,
            'measured_liters' => 9985.00,
        ])->assertRedirect();

        $this->tank->refresh();
        $this->petrol->refresh();
        $this->assertEquals(9985.00, (float) $this->tank->current_stock);
        $this->assertEquals(9985.00, (float) $this->petrol->current_stock);
        $this->assertDatabaseHas('tank_stock_adjustments', [
            'tank_id'           => $this->tank->id,
            'adjustment_liters' => -15.00,
            'is_auto'           => 1,
        ]);

        // -80 L is critical -> must NOT auto-reconcile
        $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
            'reading_date'    => now()->toDateString(),
            'reading_context' => 'daily_routine',
            'dip_cm'          => 1180,
            'measured_liters' => 9905.00,
        ])->assertRedirect();

        $this->tank->refresh();
        $this->assertEquals(9985.00, (float) $this->tank->current_stock);
        $this->assertEquals('critical', $this->tank->last_variance_status);
    }

    public function test_bowser_delivery_pre_and_post_dip_verification_calculates_short_delivery(): void
    {
        $supplier = Party::create([
            'name' => 'Caltex Supply Depot',
            'type' => 'supplier',
        ]);

        $this->actingAs($this->owner)->post(route('purchases.store'), [
            'purchase_date'          => now()->toDateString(),
            'supplier_id'            => $supplier->id,
            'location_id'            => $this->location->id,
            'product_id'             => $this->petrol->id,
            'tank_id'                => $this->tank->id,
            'invoice_no'             => 'CAL-9001',
            'vehicle_no'             => 'TL-7788',
            'quantity'               => 5000,
            'unit_price'             => 260,
            'paid_amount'            => 0,
            'pre_delivery_dip_mm'    => 1200,
            'pre_delivery_liters'    => 10000,
            'post_delivery_dip_mm'   => 1680,
            'post_delivery_liters'   => 14975,
            'sales_during_unloading' => 0,
        ])->assertRedirect(route('purchases.index'));

        $purchase = Purchase::latest('id')->first();
        $this->assertEquals(4975.00, (float) $purchase->actual_received_liters);
        $this->assertEquals(-25.00, (float) $purchase->delivery_variance_liters);

        $this->actingAs($this->owner)->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertSee('Bowser Dip Verification')
            ->assertSee('-25.0 L');
    }

    public function test_pump_calibration_syncs_tank_stock_when_not_returned_to_tank(): void
    {
        $pump = Pump::create([
            'location_id'     => $this->location->id,
            'tank_id'         => $this->tank->id,
            'product_id'      => $this->petrol->id,
            'name'            => 'Nozzle 1',
            'code'            => 'N-01',
            'initial_reading' => 1000,
            'last_reading'    => 1000,
            'status'          => 'active',
        ]);

        $this->actingAs($this->owner)->post(route('pumps.adjust-reading', $pump), [
            'new_reading'      => 1015,
            'adjustment_type'  => 'calibration',
            'volume_tested'    => 15,
            'returned_to_tank' => 0,
            'remarks'          => 'Sample taken for lab density testing',
        ])->assertRedirect();

        $this->tank->refresh();
        $this->petrol->refresh();
        $this->assertEquals(9985.00, (float) $this->tank->current_stock);
        $this->assertEquals(9985.00, (float) $this->petrol->current_stock);
    }

    public function test_dashboard_settings_and_wet_stock_reports_render_with_variance_data(): void
    {
        $this->actingAs($this->owner)->post(route('tanks.record-dip', $this->tank), [
            'reading_date'    => now()->toDateString(),
            'reading_context' => 'daily_routine',
            'dip_cm'          => 1198,
            'measured_liters' => 9990.00,
        ])->assertRedirect();

        $this->actingAs($this->owner)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Net Unreconciled Tank Variance')
            ->assertSee('-10.0 L Short');

        $this->actingAs($this->owner)->get(route('tanks.settings'))
            ->assertOk()
            ->assertSee('Tolerance Calculation Mode');

        $this->actingAs($this->owner)->get(route('reports.tank-reconciliation'))
            ->assertOk()
            ->assertSee('Physical Dip Audit Log')
            ->assertSee('9,990.0 L');

        $this->actingAs($this->owner)->get(route('reports.daily-whatsapp'))
            ->assertOk()
            ->assertSee('Var: -10.0 L (Normal)');
    }

    public function test_tanks_crud_pages_and_required_bowser_dip_mode(): void
    {
        $this->actingAs($this->owner)->get(route('tanks.index'))
            ->assertOk()
            ->assertSee('Dip &amp; Tolerance Settings', false);

        $this->actingAs($this->owner)->get(route('tanks.create'))
            ->assertOk()
            ->assertSee('DIP VARIANCE TOLERANCE RULES');

        $this->actingAs($this->owner)->get(route('tanks.edit', $this->tank))
            ->assertOk()
            ->assertSee('DIP VARIANCE TOLERANCE RULES');

        // Update tank with custom tolerance via edit endpoint
        $this->actingAs($this->owner)->put(route('tanks.update', $this->tank), [
            'location_id'                => $this->location->id,
            'product_id'                 => $this->petrol->id,
            'tank_number'                => 'TK-01',
            'name'                       => 'Underground Tank #1 (Petrol)',
            'tank_type'                  => 'underground',
            'capacity'                   => 25000,
            'dead_stock'                 => 500,
            'use_default_tolerance'      => 0,
            'tolerance_mode'             => 'fixed_liters',
            'warning_tolerance_liters'   => 18,
            'critical_tolerance_liters'  => 35,
            'warning_tolerance_percent'  => 0.40,
            'critical_tolerance_percent' => 0.80,
            'status'                     => 'active',
        ])->assertRedirect(route('tanks.index'));

        $this->tank->refresh();
        $this->assertFalse($this->tank->use_default_tolerance);
        $this->assertEquals(18.00, (float) $this->tank->warning_tolerance_liters);

        // Require bowser pre/post dips on bulk fuel purchases
        StationSetting::setValue('tank_bowser_dip_mode', 'required', $this->owner->id);

        $this->actingAs($this->owner)->get(route('purchases.create', ['tank_id' => $this->tank->id]))
            ->assertOk()
            ->assertSee('Required for Bulk Fuel');

        $supplier = Party::create(['name' => 'PSO', 'type' => 'supplier']);
        $this->actingAs($this->owner)->post(route('purchases.store'), [
            'purchase_date' => now()->toDateString(),
            'supplier_id'   => $supplier->id,
            'product_id'    => $this->petrol->id,
            'tank_id'       => $this->tank->id,
            'quantity'      => 3000,
            'unit_price'    => 260,
        ])->assertSessionHasErrors('pre_delivery_liters');
    }
}
