<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Cashier;
use App\Models\Party;
use App\Models\ShiftClosing;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AudioFeedbackTasksTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected Cashier $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'password' => Hash::make('password123'),
        ]);

        $this->cashier = Cashier::create([
            'name' => 'Main Cashier',
            'is_active' => true,
        ]);
    }

    public function test_shift_closing_create_page_has_empty_date_and_choose_shift_placeholder(): void
    {
        $response = $this->actingAs($this->owner)->get(route('shift-closings.create'));

        $response->assertOk();
        // Closing date field should have empty value by default
        $response->assertSee('value=""', false);
        // Shift dropdown should contain the placeholder option
        $response->assertSee('<option value="">-- Choose Shift --</option>', false);
    }

    public function test_shift_closing_deletion_requires_mandatory_delete_reason(): void
    {
        $closing = ShiftClosing::create([
            'date' => now()->toDateString(),
            'shift_type' => 'morning',
            'cashier_id' => $this->cashier->id,
            'closed_by' => $this->owner->id,
            'total_sale' => 1000,
            'expected_cash' => 1000,
            'total_counted_cash' => 1000,
            'difference' => 0,
            'status' => 'submitted',
        ]);

        // Attempt delete without delete_reason
        $responseFail = $this->actingAs($this->owner)->delete(route('shift-closings.destroy', $closing), []);
        $responseFail->assertSessionHasErrors(['delete_reason']);

        // Delete with mandatory delete_reason
        $responseOk = $this->actingAs($this->owner)->delete(route('shift-closings.destroy', $closing), [
            'delete_reason' => 'Duplicate sheet entry entered by mistake',
        ]);

        $responseOk->assertRedirect(route('shift-closings.index'));
        $this->assertSoftDeleted('shift_closings', ['id' => $closing->id]);
        $trashed = ShiftClosing::withTrashed()->find($closing->id);
        $this->assertEquals('Duplicate sheet entry entered by mistake', $trashed->delete_reason);
        $this->assertEquals($this->owner->id, $trashed->deleted_by);

        // Verify trash view displays the reason
        $trashView = $this->actingAs($this->owner)->get(route('shift-closings.index', ['view' => 'trash']));
        $trashView->assertOk();
        $trashView->assertSee('Duplicate sheet entry entered by mistake');
    }

    public function test_transaction_deletion_requires_mandatory_delete_reason(): void
    {
        $account = Account::getCashAccount();
        $party = Party::create([
            'name' => 'Supplier Alpha',
            'type' => 'supplier',
            'opening_balance' => 0,
            'current_balance' => 5000,
        ]);

        $tx = Transaction::create([
            'date' => now()->toDateString(),
            'type' => 'payment_out',
            'party_id' => $party->id,
            'account_id' => $account->id,
            'amount' => 1000,
            'description' => 'Advance payment',
            'created_by' => $this->owner->id,
            'status' => 'submitted',
        ]);

        // Attempt delete without delete_reason
        $responseFail = $this->actingAs($this->owner)->delete(route('transactions.destroy', $tx), []);
        $responseFail->assertSessionHasErrors(['delete_reason']);

        // Delete with mandatory delete_reason
        $responseOk = $this->actingAs($this->owner)->delete(route('transactions.destroy', $tx), [
            'delete_reason' => 'Wrong amount entered on voucher',
        ]);

        $responseOk->assertRedirect(route('transactions.index'));
        $this->assertSoftDeleted('transactions', ['id' => $tx->id]);
        $trashedTx = Transaction::withTrashed()->find($tx->id);
        $this->assertEquals('Wrong amount entered on voucher', $trashedTx->delete_reason);
        $this->assertEquals($this->owner->id, $trashedTx->deleted_by);

        // Verify trash view displays the reason
        $trashView = $this->actingAs($this->owner)->get(route('transactions.index', ['view' => 'trash']));
        $trashView->assertOk();
        $trashView->assertSee('Wrong amount entered on voucher');
    }

    public function test_party_name_is_locked_and_cannot_be_changed_via_edit(): void
    {
        $party = Party::create([
            'name' => 'Original Medicine Co',
            'type' => 'supplier',
            'phone' => '03001112233',
            'address' => 'Shop 12 Medicine Market',
            'opening_balance' => 0,
            'current_balance' => 0,
        ]);

        // Verify edit view and index modal render party name as locked/disabled
        $editView = $this->actingAs($this->owner)->get(route('parties.edit', $party));
        $editView->assertOk();
        $editView->assertSee('Party name cannot be edited once party is created');
        $editView->assertSee('Original Medicine Co');

        $indexView = $this->actingAs($this->owner)->get(route('parties.index'));
        $indexView->assertOk();
        $indexView->assertSee('Party name cannot be edited once party is created');

        // Attempt update with a new name
        $updateResponse = $this->actingAs($this->owner)->put(route('parties.update', $party), [
            'name' => 'Hacked New Name',
            'type' => 'customer',
            'phone' => '03449998877',
            'address' => 'Updated Address 456',
        ]);

        $updateResponse->assertRedirect(route('parties.index'));
        $fresh = $party->fresh();
        // Name must remain unchanged
        $this->assertEquals('Original Medicine Co', $fresh->name);
        // Address and phone are updated
        $this->assertEquals('03449998877', $fresh->phone);
        $this->assertEquals('Updated Address 456', $fresh->address);
    }
}
