<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_uses_clear_business_labels_and_links_recent_invoices(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => uniqid('demo-store-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dashboard Customer',
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Dashboard Product',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 3,
            'low_stock_alert' => 5,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-DASHBOARD',
            'sale_date' => now()->toDateString(),
            'subtotal' => 300,
            'total' => 300,
            'paid_amount' => 150,
            'remaining_amount' => 150,
            'status' => 'partial',
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 150,
            'total' => 300,
        ]);

        $this
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sales Today')
            ->assertSee('Sales in Selected Period')
            ->assertSee('Cost of Goods Sold')
            ->assertSee('Low Stock Items')
            ->assertSee('Partially Paid')
            ->assertSee('Invoice Total')
            ->assertSee('class="navbar-collapse"', false)
            ->assertDontSee('class="collapse navbar-collapse"', false)
            ->assertSee('data-sidebar-group-toggle="store-operation-links"', false)
            ->assertSee('id="store-operation-links"', false)
            ->assertDontSee('<details', false)
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('data-lucide="chevron-left"', false)
            ->assertSee('data-lucide="chevron-right"', false)
            ->assertSee('class="sidebar-footer"', false)
            ->assertSee('class="sidebar-signout"', false)
            ->assertSee('class="user-menu-toggle"', false)
            ->assertSee('class="topbar-icon-button ', false)
            ->assertSee('data-lucide="bell"', false)
            ->assertSee('class="topbar-identity"', false)
            ->assertSee('class="user-menu-summary"', false)
            ->assertSee('class="dropdown-menu dropdown-menu-end user-menu-dropdown"', false)
            ->assertSee('Profile Settings')
            ->assertSee(route('profile.edit'), false)
            ->assertDontSee('class="sidebar-user"', false)
            ->assertSee('class="nav-quick-action"', false)
            ->assertDontSee('Add Product')
            ->assertDontSee('Create Invoice')
            ->assertDontSee('Add Customer')
            ->assertDontSee('Create Purchase')
            ->assertDontSee('Add Supplier')
            ->assertDontSee('Create Expense')
            ->assertSee(route('invoices.show', $invoice), false);
    }
}
