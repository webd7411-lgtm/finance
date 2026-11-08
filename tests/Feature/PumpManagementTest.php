<?php

namespace Tests\Feature;

use App\Models\Pump;
use App\Models\PumpMeterReading;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PumpManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->product = Product::create([
            'name' => 'Test Super Petrol (RON 92)',
            'code' => 'TEST-PET-92',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 270.00,
            'sale_price' => 285.00,
            'current_stock' => 15000.00,
            'status' => 'active',
        ]);
    }

    public function test_pumps_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('pumps.index'));
        $response->assertStatus(200);
        $response->assertSee('Petrol Pump Machines &amp; Meter Readings', false);
    }

    public function test_pumps_create_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('pumps.create'));
        $response->assertStatus(200);
        $response->assertSee('Register New Pump Machine / Dispenser');
    }

    public function test_can_create_new_pump_machine_and_records_baseline_reading(): void
    {
        $data = [
            'name' => 'Dispenser Alpha 01',
            'code' => 'DISP-A1',
            'product_id' => $this->product->id,
            'tank_no' => 'Underground Tank #1',
            'nozzle_number' => 'Nozzle 1 & 2',
            'meter_type' => 'electronic',
            'initial_reading' => 5000.00,
            'last_reading' => 5000.00,
            'status' => 'active',
            'description' => 'Test electronic dual nozzle dispenser',
        ];

        $response = $this->actingAs($this->user)->post(route('pumps.store'), $data);
        $response->assertRedirect();

        $this->assertDatabaseHas('pumps', [
            'name' => 'Dispenser Alpha 01',
            'code' => 'DISP-A1',
            'product_id' => $this->product->id,
            'initial_reading' => 5000.00,
            'last_reading' => 5000.00,
            'status' => 'active',
        ]);

        $pump = Pump::where('code', 'DISP-A1')->first();
        $this->assertNotNull($pump);

        $this->assertDatabaseHas('pump_meter_readings', [
            'pump_id' => $pump->id,
            'reading_type' => 'initial_setup',
            'start_reading' => 5000.00,
            'end_reading' => 5000.00,
        ]);
    }

    public function test_can_calibrate_meter_reading(): void
    {
        $pump = Pump::create([
            'name' => 'Dispenser Calib Test',
            'code' => 'CALIB-01',
            'product_id' => $this->product->id,
            'initial_reading' => 1000.00,
            'last_reading' => 1000.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post(route('pumps.adjust-reading', $pump->id), [
            'new_reading' => 1010.00,
            'adjustment_type' => 'calibration',
            'volume_tested' => 10.00,
            'returned_to_tank' => 1,
            'remarks' => 'Annual W&M Volumetric inspection test passed.',
        ]);

        $response->assertRedirect();

        $pump->refresh();
        $this->assertEquals(1010.00, (float) $pump->last_reading);

        $this->assertDatabaseHas('pump_meter_readings', [
            'pump_id' => $pump->id,
            'reading_type' => 'calibration',
            'start_reading' => 1000.00,
            'end_reading' => 1010.00,
            'volume_dispensed' => 10.00,
        ]);
    }

    public function test_quick_store_ajax_returns_json(): void
    {
        $data = [
            'name' => 'Dispenser Modal Test',
            'code' => 'MODAL-01',
            'product_id' => $this->product->id,
            'meter_type' => 'electronic',
            'initial_reading' => 2000.00,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->user)->postJson(route('pumps.quick-store'), $data);
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Pump machine added successfully.',
        ]);
    }
}
