<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_purchases_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get(route('purchases.index'));
        $response->assertStatus(200);
        $response->assertSee('Stock &amp; Fuel Purchase Management', false);
    }

    public function test_purchases_create_page_is_accessible(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->user)->get(route('purchases.create'));
        $response->assertStatus(200);
        $response->assertSee('Record Stock / Fuel Purchase');
    }

    public function test_can_record_fuel_purchase_with_partial_payment(): void
    {
        $supplier = Party::create([
            'name' => 'Pakistan State Oil (PSO)',
            'type' => 'supplier',
            'opening_balance' => 0,
            'current_balance' => 10000.00,
        ]);

        $product = Product::create([
            'name' => 'High Octane 97',
            'code' => 'HOBC-97',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 290.00,
            'sale_price' => 310.00,
            'current_stock' => 1000.00,
            'status' => 'active',
        ]);

        $account = Account::create([
            'name' => 'HBL Main Bank Account',
            'type' => 'bank',
            'opening_balance' => 2000000.00,
            'current_balance' => 2000000.00,
            'status' => 'active',
        ]);

        $purchaseData = [
            'purchase_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'invoice_no' => 'INV-PSO-8899',
            'vehicle_no' => 'TL-5544',
            'quantity' => '5000',
            'unit_price' => '295.00',
            'paid_amount' => '1000000.00',
            'payment_account_id' => $account->id,
            'notes' => '5000L bowser drop into Tank 2',
        ];

        $response = $this->actingAs($this->user)->post(route('purchases.store'), $purchaseData);

        $response->assertRedirect(route('purchases.index'));
        $response->assertSessionHas('success');

        // Check purchase record created
        $this->assertDatabaseHas('purchases', [
            'invoice_no' => 'INV-PSO-8899',
            'vehicle_no' => 'TL-5544',
            'quantity' => 5000.00,
            'unit_price' => 295.00,
            'total_amount' => 1475000.00, // 5000 * 295
            'paid_amount' => 1000000.00,
        ]);

        // Check product stock incremented
        $product->refresh();
        $this->assertEquals(6000.00, $product->current_stock); // 1000 + 5000
        $this->assertEquals(295.00, $product->purchase_price);

        // Check supplier payable balance increased by unpaid amount (1,475,000 - 1,000,000 = 475,000)
        $supplier->refresh();
        $this->assertEquals(485000.00, $supplier->current_balance); // 10,000 + 475,000

        // Check account balance deducted
        $account->refresh();
        $this->assertEquals(1000000.00, $account->current_balance); // 2,000,000 - 1,000,000

        // Check purchase bill transaction logged for ledger
        $this->assertDatabaseHas('transactions', [
            'type' => 'purchase_bill',
            'party_id' => $supplier->id,
            'amount' => 1475000.00,
            'bill_no' => 'INV-PSO-8899',
        ]);

        // Check payment transaction logged
        $this->assertDatabaseHas('transactions', [
            'type' => 'payment_out',
            'party_id' => $supplier->id,
            'account_id' => $account->id,
            'amount' => 1000000.00,
        ]);
    }

    public function test_purchase_bill_appears_in_supplier_ledger(): void
    {
        $supplier = Party::create([
            'name' => 'Hasscol Petroleum',
            'type' => 'supplier',
            'is_credit' => true,
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $product = Product::create([
            'name' => 'Super Petrol',
            'code' => 'PET-01',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 270.00,
            'sale_price' => 282.00,
            'current_stock' => 5000.00,
            'status' => 'active',
        ]);

        // Record a purchase on credit (paid_amount = 0)
        $this->actingAs($this->user)->post(route('purchases.store'), [
            'purchase_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'invoice_no' => 'HASCOL-7788',
            'vehicle_no' => 'JU-9900',
            'quantity' => '1000',
            'unit_price' => '270.00',
            'paid_amount' => '0',
            'notes' => 'Tank delivery',
        ]);

        // Visit supplier's ledger
        $ledgerResponse = $this->actingAs($this->user)->get(route('ledgers.party', [
            'party' => $supplier->id,
            'from_date' => now()->subDay()->toDateString(),
            'to_date' => now()->addDay()->toDateString(),
        ]));

        $ledgerResponse->assertStatus(200);
        $ledgerResponse->assertSee('Hasscol Petroleum');
        $ledgerResponse->assertSee('HASCOL-7788');
        $ledgerResponse->assertSee('270,000.00');
    }

    public function test_purchase_requires_valid_data(): void
    {
        $response = $this->actingAs($this->user)->post(route('purchases.store'), [
            'purchase_date' => '',
            'supplier_id' => '',
            'product_id' => '',
            'quantity' => '0',
            'unit_price' => '',
        ]);

        $response->assertSessionHasErrors(['purchase_date', 'supplier_id', 'product_id', 'quantity', 'unit_price']);
    }

    public function test_deleting_purchase_reverses_stock_and_balances(): void
    {
        $supplier = Party::create([
            'name' => 'Total Parco',
            'type' => 'supplier',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $product = Product::create([
            'name' => 'Diesel HSD',
            'code' => 'HSD-01',
            'category' => 'fuel',
            'unit' => 'liter',
            'is_fuel' => true,
            'purchase_price' => 270.00,
            'sale_price' => 285.00,
            'current_stock' => 500.00,
            'status' => 'active',
        ]);

        $account = Account::create([
            'name' => 'Cash in Safe',
            'type' => 'cash',
            'opening_balance' => 500000.00,
            'current_balance' => 500000.00,
            'status' => 'active',
        ]);

        // Purchase 2000 liters at 270 = 540,000; paid 200,000; unpaid 340,000
        $this->actingAs($this->user)->post(route('purchases.store'), [
            'purchase_date' => now()->toDateString(),
            'supplier_id' => $supplier->id,
            'product_id' => $product->id,
            'quantity' => '2000',
            'unit_price' => '270.00',
            'paid_amount' => '200000.00',
            'payment_account_id' => $account->id,
        ]);

        $product->refresh();
        $this->assertEquals(2500.00, $product->current_stock);

        $purchase = Purchase::first();
        $this->assertNotNull($purchase);

        // Delete purchase
        $deleteResponse = $this->actingAs($this->user)->delete(route('purchases.destroy', $purchase));
        $deleteResponse->assertRedirect(route('purchases.index'));

        // Check soft-deleted
        $this->assertSoftDeleted('purchases', ['id' => $purchase->id]);

        // Stock reversed
        $product->refresh();
        $this->assertEquals(500.00, $product->current_stock);

        // Supplier balance reversed
        $supplier->refresh();
        $this->assertEquals(0.00, $supplier->current_balance);

        // Account balance restored
        $account->refresh();
        $this->assertEquals(500000.00, $account->current_balance);
    }
}
