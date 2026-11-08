<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles
        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'incharge', 'guard_name' => 'web']);
        Role::create(['name' => 'cashier', 'guard_name' => 'web']);

        // Create sample permissions
        Permission::create(['name' => 'dashboard.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'shift-closings.view', 'guard_name' => 'web']);
        Permission::create(['name' => 'shift-closings.create', 'guard_name' => 'web']);
        Permission::create(['name' => 'products.view', 'guard_name' => 'web']);

        $this->owner = User::factory()->create([
            'role' => 'owner',
            'status' => 'active',
        ]);
        $this->owner->assignRole('owner');

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'status' => 'active',
        ]);
        $this->cashier->assignRole('cashier');
    }

    public function test_owner_can_access_roles_index(): void
    {
        $response = $this->actingAs($this->owner)->get(route('roles.index'));
        $response->assertStatus(200);
        $response->assertSee('Roles & Access Control');
        $response->assertSee('owner');
        $response->assertSee('incharge');
        $response->assertSee('cashier');
    }

    public function test_cashier_is_forbidden_from_roles_index(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('roles.index'));
        $response->assertStatus(403);
    }

    public function test_can_create_custom_role_with_permissions(): void
    {
        $response = $this->actingAs($this->owner)->post(route('roles.store'), [
            'name' => 'Accountant',
            'permissions' => ['dashboard.view', 'products.view'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => 'accountant']);

        $role = Role::where('name', 'accountant')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('dashboard.view'));
        $this->assertTrue($role->hasPermissionTo('products.view'));
        $this->assertFalse($role->hasPermissionTo('shift-closings.create'));
    }

    public function test_can_update_role_permissions(): void
    {
        $role = Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
        $role->syncPermissions(['dashboard.view']);

        $response = $this->actingAs($this->owner)->put(route('roles.update', $role->id), [
            'name' => 'Supervisor',
            'permissions' => ['shift-closings.view', 'shift-closings.create'],
        ]);

        $response->assertRedirect(route('roles.index'));

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('shift-closings.view'));
        $this->assertTrue($role->hasPermissionTo('shift-closings.create'));
        $this->assertFalse($role->hasPermissionTo('dashboard.view'));
    }

    public function test_cannot_delete_system_core_role(): void
    {
        $cashierRole = Role::where('name', 'cashier')->first();

        $response = $this->actingAs($this->owner)->delete(route('roles.destroy', $cashierRole->id));
        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['name' => 'cashier']);
    }

    public function test_can_delete_custom_role_without_assigned_users(): void
    {
        $customRole = Role::create(['name' => 'trainee', 'guard_name' => 'web']);

        $response = $this->actingAs($this->owner)->delete(route('roles.destroy', $customRole->id));
        $response->assertRedirect(route('roles.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('roles', ['name' => 'trainee']);
    }
}
