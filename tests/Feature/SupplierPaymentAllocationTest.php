<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierPaymentAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_payment_is_allocated_to_oldest_purchases_first(): void
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

        $firstPurchase = Purchase::create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-OLD',
            'purchase_date' => '2026-06-01',
            'subtotal' => 10000,
            'total' => 10000,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
            'status' => 'unpaid',
        ]);

        $secondPurchase = Purchase::create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-NEW',
            'purchase_date' => '2026-06-02',
            'subtotal' => 5000,
            'total' => 5000,
            'paid_amount' => 0,
            'remaining_amount' => 5000,
            'status' => 'unpaid',
        ]);

        $this
            ->post(route('supplier-payments.store'), [
                'supplier_id' => $supplier->id,
                'payment_date' => '2026-06-02',
                'amount' => 14000,
                'payment_method' => 'cash',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('purchases', [
            'id' => $firstPurchase->id,
            'paid_amount' => 10000,
            'remaining_amount' => 0,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('purchases', [
            'id' => $secondPurchase->id,
            'paid_amount' => 4000,
            'remaining_amount' => 1000,
            'status' => 'partial',
        ]);
    }

    public function test_supplier_payment_cannot_exceed_outstanding_balance(): void
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
            'purchase_no' => 'PUR-ONLY',
            'purchase_date' => '2026-06-01',
            'subtotal' => 10000,
            'total' => 10000,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
            'status' => 'unpaid',
        ]);

        $this
            ->from(route('supplier.account', $supplier))
            ->post(route('supplier-payments.store'), [
                'supplier_id' => $supplier->id,
                'payment_date' => '2026-06-02',
                'amount' => 11000,
                'payment_method' => 'cash',
                'source' => 'account',
            ])
            ->assertRedirect(route('supplier.account', $supplier))
            ->assertSessionHasErrors('amount');
    }
}
