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
            ->assertSee(route('invoices.show', $invoice), false);
    }
}
