<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PartyDuplicateTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.url' => 'http://localhost']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function getTestUser(): User
    {
        return User::first() ?? User::create([
            'name' => 'Test User',
            'email' => 'test_user_' . time() . '@example.com',
            'role' => 'owner',
            'password' => Hash::make('password123'),
        ]);
    }

    public function test_parties_check_duplicate_returns_matches_and_detects_exact_match(): void
    {
        $user = $this->getTestUser();

        Party::create([
            'name' => 'UniqueTest Apex Traders',
            'type' => 'supplier',
            'phone' => '03001234567',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        Party::create([
            'name' => 'UniqueTest Apex Motors',
            'type' => 'customer',
            'phone' => '03111234567',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        // Query partial match
        $response = $this->actingAs($user)->getJson('/parties/check-duplicate?name=' . urlencode('UniqueTest Apex'));
        if ($response->getStatusCode() === 404) {
            dump($response->status(), $response->json(), config('app.url'));
        }
        $response->assertOk();
        $response->assertJson([
            'exact_match' => false,
        ]);
        $response->assertJsonCount(2, 'suggestions');

        // Query exact match (case-insensitive)
        $responseExact = $this->actingAs($user)->getJson('/parties/check-duplicate?name=' . urlencode('uniquetest apex traders'));
        $responseExact->assertOk();
        $responseExact->assertJson([
            'exact_match' => true,
            'exact_party' => [
                'name' => 'UniqueTest Apex Traders',
                'type' => 'Supplier',
            ],
        ]);
    }

    public function test_store_party_rejects_duplicate_name(): void
    {
        $user = $this->getTestUser();

        Party::create([
            'name' => 'UniqueTest Alpha Corp',
            'type' => 'supplier',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $response = $this->actingAs($user)->post('/parties', [
            'name' => 'UniqueTest Alpha Corp',
            'type' => 'customer',
            'opening_balance' => 0,
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals(1, Party::where('name', 'UniqueTest Alpha Corp')->count());
    }

    public function test_party_name_is_locked_and_cannot_be_changed_on_update(): void
    {
        $user = $this->getTestUser();

        $party = Party::create([
            'name' => 'Original Party Name',
            'type' => 'supplier',
            'phone' => '03001234567',
            'address' => 'Old Address',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        // Attempt to rename party while updating phone and address
        $response = $this->actingAs($user)->put("/parties/{$party->id}", [
            'name' => 'Attempted New Name',
            'type' => 'trader',
            'phone' => '03119876543',
            'address' => 'New Address',
        ]);

        $response->assertSessionHasNoErrors();
        $fresh = $party->fresh();
        // Party name must remain locked and unchanged
        $this->assertEquals('Original Party Name', $fresh->name);
        // Other fields should update
        $this->assertEquals('trader', $fresh->type);
        $this->assertEquals('03119876543', $fresh->phone);
        $this->assertEquals('New Address', $fresh->address);
    }

    public function test_parties_are_sorted_alphabetically_a_to_z_by_default(): void
    {
        $user = $this->getTestUser();

        // Create parties in mixed order
        Party::create(['name' => 'Zara Traders', 'type' => 'supplier']);
        Party::create(['name' => 'Alpha Co', 'type' => 'customer']);
        Party::create(['name' => 'Mango Imports', 'type' => 'trader']);

        $response = $this->actingAs($user)->get('/parties');
        $response->assertOk();

        $parties = $response->viewData('parties');
        $names = $parties->pluck('name')->values()->toArray();

        // Check that names are sorted alphabetically
        $sortedNames = $names;
        natcasesort($sortedNames);
        $sortedNames = array_values($sortedNames);

        $this->assertEquals($sortedNames, $names);
    }
}
