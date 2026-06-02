<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPaymentAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_statement_payment_is_allocated_to_oldest_invoices_first(): void
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
            'name' => 'Walk-in Customer',
        ]);

        $firstInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-OLD',
            'sale_date' => '2026-06-01',
            'subtotal' => 10000,
            'total' => 10000,
            'paid_amount' => 0,
            'remaining_amount' => 10000,
            'status' => 'unpaid',
        ]);

        $secondInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-NEW',
            'sale_date' => '2026-06-02',
            'subtotal' => 5000,
            'total' => 5000,
            'paid_amount' => 0,
            'remaining_amount' => 5000,
            'status' => 'unpaid',
        ]);

        $this
            ->post(route('customer-payments.store', $customer), [
                'amount' => 14000,
                'payment_method' => 'cash',
                'payment_date' => '2026-06-02',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'id' => $firstInvoice->id,
            'paid_amount' => 10000,
            'remaining_amount' => 0,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $secondInvoice->id,
            'paid_amount' => 4000,
            'remaining_amount' => 1000,
            'status' => 'partial',
        ]);
    }
}
