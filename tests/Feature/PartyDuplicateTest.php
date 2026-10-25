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

    public function test_update_party_rejects_duplicate_name_of_another_party(): void
    {
        $user = $this->getTestUser();

        $party1 = Party::create([
            'name' => 'UniqueTest Existing 1',
            'type' => 'supplier',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        $party2 = Party::create([
            'name' => 'UniqueTest Existing 2',
            'type' => 'customer',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        // Attempt to rename party2 to party1's name
        $response = $this->actingAs($user)->put("/parties/{$party2->id}", [
            'name' => 'UniqueTest Existing 1',
            'type' => 'customer',
        ]);

        $response->assertSessionHasErrors(['name']);

        // Renaming party2 without conflict should succeed
        $responseOk = $this->actingAs($user)->put("/parties/{$party2->id}", [
            'name' => 'UniqueTest Existing 2 Updated',
            'type' => 'customer',
        ]);

        $responseOk->assertSessionHasNoErrors();
        $this->assertEquals('UniqueTest Existing 2 Updated', $party2->fresh()->name);
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
