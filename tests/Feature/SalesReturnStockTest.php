<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SalesReturnStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_return_increases_product_stock(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Walk In Customer',
        ]);

        $product = Product::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Returned Sale Product',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 7,
            'low_stock_alert' => 2,
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $user->tenant_id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-RETURN-STOCK',
            'sale_date' => '2026-06-01',
            'subtotal' => 1500,
            'total' => 1500,
            'paid_amount' => 0,
            'remaining_amount' => 1500,
            'status' => 'unpaid',
        ]);

        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'price' => 150,
            'total' => 1500,
        ]);

        $this
            ->post(route('sales-returns.store', $invoice), [
                'return_date' => '2026-06-02',
                'items' => [
                    $invoiceItem->id => [
                        'quantity' => 3,
                    ],
                ],
                'notes' => 'Customer returned items',
            ])
            ->assertRedirect(route('invoices.show', $invoice));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sales_return',
            'direction' => 'in',
            'quantity' => 3,
        ]);
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
