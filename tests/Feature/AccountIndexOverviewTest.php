<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountIndexOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_index_uses_clear_receivable_language(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Due Customer',
            'phone' => '03000000000',
            'address' => 'Main Market',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $user->tenant_id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-CUSTOMER-DUE',
            'sale_date' => '2026-06-03',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'status' => 'partial',
        ]);

        CustomerPayment::create([
            'tenant_id' => $user->tenant_id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 400,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-03',
        ]);

        $this
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Customers Owing Money')
            ->assertSee('Customer Owes Us')
            ->assertSee('Amount Received')
            ->assertSee('Payment Due')
            ->assertSee('Rs 600.00')
            ->assertDontSee('Remaining Amount');
    }

    public function test_supplier_index_uses_clear_payable_language(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Due Supplier',
            'phone' => '03111111111',
            'address' => 'Supplier Road',
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-SUPPLIER-DUE',
            'purchase_date' => '2026-06-03',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 300,
            'remaining_amount' => 700,
            'status' => 'partial',
        ]);

        SupplierPayment::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_id' => $purchase->id,
            'amount' => 300,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-03',
        ]);

        $this
            ->get(route('suppliers.index'))
            ->assertOk()
            ->assertSee('Suppliers to Pay')
            ->assertSee('Still Payable to Supplier')
            ->assertSee('Amount Paid')
            ->assertSee('Payment Due')
            ->assertSee('Rs 700.00')
            ->assertDontSee('Remaining Amount');
    }

    private function ownerUser(): User
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => uniqid('demo-store-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        return $user;
    }
}
