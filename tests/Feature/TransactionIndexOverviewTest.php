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

class TransactionIndexOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_index_uses_clear_receivable_language(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Invoice Customer',
        ]);

        Invoice::create([
            'tenant_id' => $user->tenant_id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-INDEX',
            'sale_date' => '2026-06-03',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'status' => 'partial',
        ]);

        $this
            ->get(route('invoices.index'))
            ->assertOk()
            ->assertSee('Customer Owes Us')
            ->assertSee('Amount Received')
            ->assertSee('Open Invoices')
            ->assertSee('Rs 600.00')
            ->assertSee('INV-INDEX')
            ->assertDontSee('Remaining Amount')
            ->assertDontSee('Pay');
    }

    public function test_purchase_index_uses_clear_payable_language_and_detail_first_actions(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Purchase Supplier',
        ]);

        Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-INDEX',
            'purchase_date' => '2026-06-03',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 300,
            'remaining_amount' => 700,
            'status' => 'partial',
        ]);

        $this
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertSee('Still Payable to Supplier')
            ->assertSee('Amount Paid')
            ->assertSee('Open Purchases')
            ->assertSee('Rs 700.00')
            ->assertSee('PUR-INDEX')
            ->assertDontSee('Remaining Amount')
            ->assertDontSee('>Pay<', false);
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
