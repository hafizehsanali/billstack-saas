<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertsCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_center_lists_low_stock_products(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        Product::create([
            'name' => 'Out Item',
            'purchase_price' => 50,
            'selling_price' => 80,
            'stock_quantity' => 0,
            'low_stock_alert' => 5,
        ]);

        Product::create([
            'name' => 'Low Item',
            'purchase_price' => 60,
            'selling_price' => 90,
            'stock_quantity' => 3,
            'low_stock_alert' => 5,
        ]);

        Product::create([
            'name' => 'Healthy Item',
            'purchase_price' => 70,
            'selling_price' => 100,
            'stock_quantity' => 12,
            'low_stock_alert' => 5,
        ]);

        $this
            ->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('Out Item')
            ->assertSee('Low Item')
            ->assertDontSee('Healthy Item');
    }

    public function test_alerts_center_lists_customer_and_supplier_payment_dues(): void
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
            'name' => 'Due Customer',
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-DUE',
            'sale_date' => '2026-06-01',
            'subtotal' => 10000,
            'total' => 10000,
            'paid_amount' => 3000,
            'remaining_amount' => 7000,
            'status' => 'partial',
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-DUE-SECOND',
            'sale_date' => '2026-06-03',
            'subtotal' => 3000,
            'total' => 3000,
            'paid_amount' => 0,
            'remaining_amount' => 3000,
            'status' => 'unpaid',
        ]);

        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-PAID',
            'sale_date' => '2026-06-02',
            'subtotal' => 5000,
            'total' => 5000,
            'paid_amount' => 5000,
            'remaining_amount' => 0,
            'status' => 'paid',
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Due Supplier',
        ]);

        Purchase::create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-DUE',
            'purchase_date' => '2026-06-01',
            'subtotal' => 8000,
            'total' => 8000,
            'paid_amount' => 0,
            'remaining_amount' => 8000,
            'status' => 'unpaid',
        ]);

        $this
            ->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('Due Customer')
            ->assertSee('10,000.00')
            ->assertSee('Due Supplier')
            ->assertSee('8,000.00')
            ->assertSee('2')
            ->assertDontSee('INV-PAID');
    }
}
