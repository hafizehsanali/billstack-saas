<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountWorkflowWordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_statement_uses_clear_payment_language(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Statement Customer',
        ]);

        Invoice::create([
            'tenant_id' => $user->tenant_id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-STATEMENT',
            'sale_date' => '2026-06-03',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $this
            ->get(route('customers.statement', $customer))
            ->assertOk()
            ->assertSee('Customer Statement')
            ->assertSee('Amount Received from Customer')
            ->assertSee('Customer owes us: Rs 1,000.00')
            ->assertSee('Payment / Return Credit')
            ->assertSee('Save Payment');
    }

    public function test_supplier_account_and_payment_form_use_clear_payable_language(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Statement Supplier',
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-STATEMENT',
            'purchase_date' => '2026-06-03',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $this
            ->get(route('supplier.account', $supplier))
            ->assertOk()
            ->assertSee('Total Supplier Bills')
            ->assertSee('Paid to Supplier')
            ->assertSee('Still Payable to Supplier')
            ->assertSee('Record Supplier Payment')
            ->assertSee('Still payable: Rs 1,000.00');

        $this
            ->get(route('supplier-payments.create', [$supplier, $purchase]))
            ->assertOk()
            ->assertSee('Record Supplier Payment')
            ->assertSee('Still payable: Rs 1,000.00')
            ->assertSee('Amount Paid to Supplier')
            ->assertSee('Save Supplier Payment');
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
