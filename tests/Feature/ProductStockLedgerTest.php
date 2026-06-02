<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductStockLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_ledger_shows_friendly_labels_and_clickable_references(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $product = Product::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Ledger Product',
            'sku' => 'LP-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 8,
            'low_stock_alert' => 2,
        ]);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Ledger Supplier',
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-STOCK-LEDGER',
            'purchase_date' => '2026-06-01',
            'subtotal' => 500,
            'total' => 500,
            'paid_amount' => 0,
            'remaining_amount' => 500,
            'status' => 'unpaid',
        ]);

        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'purchase_price' => 100,
            'line_total' => 500,
        ]);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Ledger Customer',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $user->tenant_id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-STOCK-LEDGER',
            'sale_date' => '2026-06-02',
            'subtotal' => 300,
            'total' => 300,
            'paid_amount' => 0,
            'remaining_amount' => 300,
            'status' => 'unpaid',
        ]);

        $invoiceItem = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 150,
            'total' => 300,
        ]);

        StockMovement::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'type' => 'purchase',
            'direction' => 'in',
            'quantity' => 5,
            'unit_cost' => 100,
            'stock_after' => 10,
            'source_type' => PurchaseItem::class,
            'source_id' => $purchaseItem->id,
            'reference_no' => $purchase->purchase_no,
            'movement_date' => '2026-06-01',
        ]);

        StockMovement::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'type' => 'sale',
            'direction' => 'out',
            'quantity' => 2,
            'unit_cost' => 100,
            'unit_price' => 150,
            'stock_after' => 8,
            'source_type' => InvoiceItem::class,
            'source_id' => $invoiceItem->id,
            'reference_no' => $invoice->invoice_no,
            'movement_date' => '2026-06-02',
        ]);

        $this
            ->get(route('products.stock-ledger', $product))
            ->assertOk()
            ->assertSee('Ledger Product')
            ->assertSee('SKU: LP-001')
            ->assertSee('Purchased from Supplier')
            ->assertSee('Sold to Customer')
            ->assertSee('Stock In')
            ->assertSee('Stock Out')
            ->assertSee(route('purchases.show', $purchase), false)
            ->assertSee(route('invoices.show', $invoice), false);
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
