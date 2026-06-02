<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_with_invoice_history_cannot_be_deleted(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Regular Customer',
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-LOCKED',
            'sale_date' => '2026-06-01',
            'subtotal' => 10000,
            'total' => 10000,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
            'status' => 'unpaid',
        ]);

        $this
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect()
            ->assertSessionHasErrors('customer');

        $this->assertNotSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_unused_customer_can_be_deleted(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unused Customer',
        ]);

        $this
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);
    }
}
