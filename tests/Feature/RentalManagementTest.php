<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\RentalCollection;
use App\Models\StationRental;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RentalManagementTest extends TestCase
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        if (! Schema::hasTable('users')) {
            $migrations = glob(database_path('migrations/*.php')) ?: [];
            sort($migrations);

            foreach ($migrations as $file) {
                if (str_contains($file, '2026_11_23_220000_modify_shift_type_in_shift_closings_table')) {
                    continue;
                }
                $migration = require $file;
                if (is_object($migration) && method_exists($migration, 'up')) {
                    $migration->up();
                }
            }
        }

        $this->user = User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_rentals_page_uses_text_decimal_inputs_for_rent_amounts(): void
    {
        StationRental::create([
            'name' => 'Car Wash Bay',
            'facility_type' => 'car_wash',
            'tenant_name' => 'Ali Raza',
            'monthly_rent' => 120000.00,
            'due_day' => 5,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->get(route('rentals.index'));
        $response->assertStatus(200);
        $response->assertSee('type="text" inputmode="decimal" name="amount"', false);
        $response->assertSee('type="text" inputmode="decimal" name="monthly_rent"', false);
        $response->assertSee("if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button' || input.type === 'number') return false;", false);
    }

    public function test_can_store_and_update_facility_with_large_comma_formatted_monthly_rent(): void
    {
        $storeResponse = $this->actingAs($this->user)->post(route('rentals.store'), [
            'name' => 'Car Wash Service Ramp #1',
            'facility_type' => 'car_wash',
            'tenant_name' => 'Ali Raza S/o Nazir Khan',
            'tenant_phone' => '0300-1234567',
            'monthly_rent' => '120,000.00',
            'due_day' => 5,
            'notes' => 'Monthly car wash lease',
        ]);

        $storeResponse->assertRedirect(route('rentals.index'));
        $storeResponse->assertSessionHasNoErrors();

        $rental = StationRental::where('name', 'Car Wash Service Ramp #1')->firstOrFail();
        $this->assertEquals('120000.00', $rental->monthly_rent);

        $updateResponse = $this->actingAs($this->user)->put(route('rentals.update', $rental), [
            'name' => 'Car Wash Service Ramp #1',
            'facility_type' => 'car_wash',
            'tenant_name' => 'Ali Raza S/o Nazir Khan',
            'tenant_phone' => '0300-1234567',
            'monthly_rent' => '2,500,000.75',
            'due_day' => 5,
            'status' => 'active',
            'notes' => 'Updated lease rent',
        ]);

        $updateResponse->assertRedirect(route('rentals.index'));
        $updateResponse->assertSessionHasNoErrors();
        $rental->refresh();
        $this->assertEquals('2500000.75', $rental->monthly_rent);
    }

    public function test_can_collect_any_rent_amount_including_more_than_three_digits_and_decimals(): void
    {
        $bankAccount = Account::create([
            'name' => 'Meezan Bank',
            'type' => 'bank',
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
        ]);

        $rental = StationRental::create([
            'name' => 'Car Wash Bay',
            'facility_type' => 'car_wash',
            'tenant_name' => 'Ali Raza S/o Nazir Khan',
            'monthly_rent' => 120000.00,
            'due_day' => 5,
            'status' => 'active',
        ]);

        // 6-digit comma-formatted rent (Rs. 120,000)
        $res1 = $this->actingAs($this->user)->post(route('rentals.collect', $rental), [
            'payment_date' => '2026-11-26',
            'rent_month' => '2026-11',
            'amount' => '120,000',
            'account_id' => $bankAccount->id,
            'receipt_no' => '2355645',
            'notes' => 'November 2026',
        ]);
        $res1->assertRedirect(route('rentals.index'));
        $res1->assertSessionHasNoErrors();

        // Large 8-digit decimal amount (Rs. 15,250,000.50)
        $res2 = $this->actingAs($this->user)->post(route('rentals.collect', $rental), [
            'payment_date' => '2026-11-26',
            'rent_month' => '2026-12',
            'amount' => '15,250,000.50',
            'account_id' => $bankAccount->id,
            'receipt_no' => '2355646',
            'notes' => 'Advance multi-year lease',
        ]);
        $res2->assertRedirect(route('rentals.index'));
        $res2->assertSessionHasNoErrors();

        // Small decimal amount below 1 (Rs. 0.50)
        $res3 = $this->actingAs($this->user)->post(route('rentals.collect', $rental), [
            'payment_date' => '2026-11-26',
            'rent_month' => '2026-12',
            'amount' => '0.50',
            'account_id' => $bankAccount->id,
            'receipt_no' => '2355647',
            'notes' => 'Adjustment',
        ]);
        $res3->assertRedirect(route('rentals.index'));
        $res3->assertSessionHasNoErrors();

        $this->assertEquals(3, RentalCollection::where('station_rental_id', $rental->id)->count());
        $this->assertDatabaseHas('rental_collections', [
            'station_rental_id' => $rental->id,
            'amount' => 120000.00,
            'receipt_no' => '2355645',
        ]);
        $this->assertDatabaseHas('rental_collections', [
            'station_rental_id' => $rental->id,
            'amount' => 15250000.50,
            'receipt_no' => '2355646',
        ]);
        $this->assertDatabaseHas('rental_collections', [
            'station_rental_id' => $rental->id,
            'amount' => 0.50,
            'receipt_no' => '2355647',
        ]);

        $bankAccount->refresh();
        $this->assertEquals('15370001.00', $bankAccount->current_balance);
        $this->assertEquals(3, Transaction::where('type', 'payment_in')->where('account_id', $bankAccount->id)->count());
    }
}
