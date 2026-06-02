<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_with_purchase_history_cannot_be_deleted(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Supplier',
        ]);

        Purchase::create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-LOCKED',
            'purchase_date' => '2026-06-01',
            'subtotal' => 10000,
            'total' => 10000,
            'remaining_amount' => 10000,
            'status' => 'unpaid',
        ]);

        $this
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect()
            ->assertSessionHasErrors('supplier');

        $this->assertNotSoftDeleted('suppliers', [
            'id' => $supplier->id,
        ]);
    }

    public function test_unused_supplier_can_be_deleted(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unused Supplier',
        ]);

        $this
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'));

        $this->assertSoftDeleted('suppliers', [
            'id' => $supplier->id,
        ]);
    }
}
