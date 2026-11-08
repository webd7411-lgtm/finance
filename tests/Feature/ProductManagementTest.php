<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Party;
use App\Models\ActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'status' => 'active',
        ]);
    }

    public function test_products_index_screen_can_be_rendered(): void
    {
        Product::create([
            'name' => 'Super Petrol (RON 92)',
            'code' => 'PET-92',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'density_or_octane' => 'RON 92',
            'tank_or_shelf' => 'Tank 1',
            'purchase_price' => 260.00,
            'sale_price' => 280.00,
            'opening_stock' => 15000,
            'current_stock' => 15000,
            'min_stock_alert' => 3000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertSee('Super Petrol (RON 92)');
        $response->assertSee('PET-92');
        $response->assertSee('280.00');
    }

    public function test_products_create_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->owner)->get(route('products.create'));

        $response->assertStatus(200);
        $response->assertSee('Register New Product / Fuel Type');
        $response->assertSee('Product Details');
        $response->assertSee('Register Product');
    }

    public function test_can_store_fuel_product_with_comma_formatted_prices(): void
    {
        $supplier = Party::create([
            'name' => 'Pakistan State Oil (PSO)',
            'type' => 'supplier',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $payload = [
            'name' => 'High Speed Diesel (HSD)',
            'code' => 'HSD-01',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => '1',
            'density_or_octane' => '0.835 kg/L',
            'tank_or_shelf' => 'Underground Tank #2',
            'supplier_id' => $supplier->id,
            'purchase_price' => '270.50',
            'sale_price' => '285.00',
            'opening_stock' => '25,000.00',
            'min_stock_alert' => '5,000.00',
            'status' => 'active',
            'description' => 'Euro-5 high speed diesel for heavy vehicles and transports.',
        ];

        $response = $this->actingAs($this->owner)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'High Speed Diesel (HSD)',
            'code' => 'HSD-01',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => 1,
            'purchase_price' => 270.50,
            'sale_price' => 285.00,
            'opening_stock' => 25000.00,
            'current_stock' => 25000.00,
            'min_stock_alert' => 5000.00,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'products',
            'action' => 'created',
        ]);
    }

    public function test_can_store_lubricant_product_and_auto_generate_code_if_blank(): void
    {
        $payload = [
            'name' => 'Caltex Havoline Formula 20W-50 (4L)',
            'code' => '',
            'category' => 'lubricant',
            'unit' => 'can',
            'is_fuel' => '0',
            'density_or_octane' => '20W-50 API SN',
            'tank_or_shelf' => 'Lube Stand Rack A',
            'purchase_price' => '3,800.00',
            'sale_price' => '4,450.00',
            'opening_stock' => '24',
            'min_stock_alert' => '6',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->owner)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('name', 'Caltex Havoline Formula 20W-50 (4L)')->first();
        $this->assertNotNull($product);
        $this->assertStringStartsWith('LUB-', $product->code);
        $this->assertEquals(3800.00, (float) $product->purchase_price);
        $this->assertEquals(4450.00, (float) $product->sale_price);
        $this->assertEquals(24, (float) $product->current_stock);
        $this->assertEquals(650.00, $product->profit_margin);
    }

    public function test_rejects_duplicate_product_name(): void
    {
        Product::create([
            'name' => 'Super Petrol (RON 92)',
            'code' => 'PET-92',
            'category' => 'fuel',
            'unit' => 'liter',
            'purchase_price' => 260.00,
            'sale_price' => 280.00,
            'opening_stock' => 1000,
            'current_stock' => 1000,
            'status' => 'active',
        ]);

        $payload = [
            'name' => 'Super Petrol (RON 92)',
            'code' => 'PET-NEW',
            'category' => 'fuel',
            'unit' => 'liter',
            'purchase_price' => 260.00,
            'sale_price' => 280.00,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->owner)
            ->from(route('products.create'))
            ->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.create'));
        $response->assertSessionHasErrors(['name']);
    }

    public function test_check_duplicate_live_endpoint_detects_match(): void
    {
        Product::create([
            'name' => 'Super Petrol (RON 92)',
            'code' => 'PET-92',
            'category' => 'fuel',
            'unit' => 'liter',
            'purchase_price' => 260.00,
            'sale_price' => 280.00,
            'opening_stock' => 5000,
            'current_stock' => 5000,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->getJson('/products/check-duplicate?name=' . urlencode('super petrol (ron 92)'));

        $response->assertStatus(200);
        $response->assertJson([
            'exact_match' => true,
            'exact_product' => [
                'name' => 'Super Petrol (RON 92)',
            ],
        ]);
    }

    public function test_can_update_product_and_logs_rate_revision(): void
    {
        $product = Product::create([
            'name' => 'Super Petrol (RON 92)',
            'code' => 'PET-92',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 260.00,
            'sale_price' => 280.00,
            'opening_stock' => 10000,
            'current_stock' => 10000,
            'min_stock_alert' => 2000,
            'status' => 'active',
        ]);

        $updatePayload = [
            'name' => 'Super Petrol (RON 92) Premium',
            'code' => 'PET-92',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => '1',
            'density_or_octane' => 'RON 92.5',
            'tank_or_shelf' => 'Tank 1',
            'purchase_price' => '265.00',
            'sale_price' => '288.50',
            'current_stock' => '9,500.00',
            'min_stock_alert' => '2,500.00',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->owner)->put(route('products.update', $product), $updatePayload);

        $response->assertRedirect(route('products.index'));

        $product->refresh();
        $this->assertEquals('Super Petrol (RON 92) Premium', $product->name);
        $this->assertEquals(288.50, (float) $product->sale_price);
        $this->assertEquals(9500.00, (float) $product->current_stock);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'products',
            'action' => 'updated',
        ]);
    }

    public function test_quick_price_update_for_fortnightly_fuel_rate_change(): void
    {
        $product = Product::create([
            'name' => 'High Speed Diesel (HSD)',
            'code' => 'HSD-01',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 270.00,
            'sale_price' => 282.00,
            'opening_stock' => 20000,
            'current_stock' => 20000,
            'status' => 'active',
        ]);

        $payload = [
            'sale_price' => '290.50',
            'purchase_price' => '278.00',
            'price_change_reason' => 'Government Fuel Rate Increase',
        ];

        $response = $this->actingAs($this->owner)->post(route('products.quick-price', $product), $payload);

        $response->assertRedirect();
        $product->refresh();
        $this->assertEquals(290.50, (float) $product->sale_price);
        $this->assertEquals(278.00, (float) $product->purchase_price);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'products',
            'action' => 'updated',
        ]);
    }

    public function test_toggle_product_status(): void
    {
        $product = Product::create([
            'name' => 'Seasonal Windshield Fluid',
            'code' => 'CARE-01',
            'category' => 'car_care',
            'unit' => 'bottle',
            'purchase_price' => 150.00,
            'sale_price' => 250.00,
            'opening_stock' => 20,
            'current_stock' => 20,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->post(route('products.toggle-status', $product));

        $response->assertRedirect();
        $product->refresh();
        $this->assertEquals('inactive', $product->status);

        // Toggle back to active
        $this->actingAs($this->owner)->post(route('products.toggle-status', $product));
        $product->refresh();
        $this->assertEquals('active', $product->status);
    }

    public function test_low_stock_scope_identifies_shortage(): void
    {
        $normalProduct = Product::create([
            'name' => 'Super Petrol (RON 92)',
            'category' => 'fuel',
            'unit' => 'liter',
            'purchase_price' => 260.00,
            'sale_price' => 280.00,
            'opening_stock' => 15000,
            'current_stock' => 12000,
            'min_stock_alert' => 3000,
            'status' => 'active',
        ]);

        $lowStockProduct = Product::create([
            'name' => 'Hi-Octane 97',
            'category' => 'fuel',
            'unit' => 'liter',
            'purchase_price' => 295.00,
            'sale_price' => 315.00,
            'opening_stock' => 5000,
            'current_stock' => 800, // Below min alert
            'min_stock_alert' => 1500,
            'status' => 'active',
        ]);

        $this->assertFalse($normalProduct->is_low_stock);
        $this->assertTrue($lowStockProduct->is_low_stock);

        $lowStockItems = Product::lowStock()->get();
        $this->assertCount(1, $lowStockItems);
        $this->assertEquals($lowStockProduct->id, $lowStockItems->first()->id);
    }

    public function test_can_soft_delete_product(): void
    {
        $product = Product::create([
            'name' => 'Old Engine Oil 10W-40',
            'category' => 'lubricant',
            'unit' => 'can',
            'purchase_price' => 2500.00,
            'sale_price' => 3000.00,
            'opening_stock' => 5,
            'current_stock' => 5,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->owner)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->assertDatabaseHas('activity_logs', [
            'module' => 'products',
            'action' => 'deleted',
        ]);
    }

    public function test_can_quick_add_category_via_modal_endpoint(): void
    {
        $response = $this->actingAs($this->owner)->postJson(route('products.categories.quick-store'), [
            'name' => 'Auto Accessories & Filters',
            'description' => 'Vehicle accessories and parts',
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'category' => [
                'name' => 'Auto Accessories & Filters',
                'slug' => 'auto_accessories_filters',
            ],
        ]);

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Auto Accessories & Filters',
            'slug' => 'auto_accessories_filters',
        ]);
    }

    public function test_can_quick_add_unit_and_store_product_with_new_unit(): void
    {
        $unitResponse = $this->actingAs($this->owner)->postJson(route('products.units.quick-store'), [
            'name' => 'Gallons (US)',
            'short_code' => 'gal',
            'description' => 'US liquid gallons',
        ]);

        $unitResponse->assertStatus(201);
        $unitResponse->assertJson([
            'success' => true,
            'unit' => [
                'name' => 'Gallons (US)',
                'short_code' => 'gal',
            ],
        ]);

        $this->assertDatabaseHas('product_units', [
            'name' => 'Gallons (US)',
            'short_code' => 'gal',
        ]);

        // Now store product using the new unit
        $productResponse = $this->actingAs($this->owner)->post(route('products.store'), [
            'name' => 'Bulk Imported Coolant',
            'code' => 'COOL-GAL-01',
            'category' => 'car_care',
            'unit' => 'gal',
            'purchase_price' => '1,200.00',
            'sale_price' => '1,500.00',
            'opening_stock' => '50',
            'status' => 'active',
        ]);

        $productResponse->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Bulk Imported Coolant',
            'unit' => 'gal',
        ]);
    }
}
