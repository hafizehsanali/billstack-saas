<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransactionCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_invoice_can_be_cancelled_and_stock_is_restored(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Walk-in Customer']);
        $product = $this->product('Invoice Product', 3);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-CANCEL',
            'sale_date' => '2026-06-07',
            'subtotal' => 200,
            'total' => 200,
            'paid_amount' => 0,
            'remaining_amount' => 200,
            'status' => 'unpaid',
        ]);

        $invoice->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100,
            'total' => 200,
        ]);

        $this
            ->patch(route('invoices.cancel', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 5,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'sale_cancel',
            'direction' => 'in',
            'quantity' => 2,
        ]);
    }

    public function test_invoice_with_payment_history_cannot_be_cancelled(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Credit Customer']);
        $product = $this->product('Paid Invoice Product', 3);

        $invoice = Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-PAID-HISTORY',
            'sale_date' => '2026-06-07',
            'subtotal' => 200,
            'total' => 200,
            'paid_amount' => 100,
            'remaining_amount' => 100,
            'status' => 'partial',
        ]);

        $invoice->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100,
            'total' => 200,
        ]);

        CustomerPayment::create([
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-07',
        ]);

        $this
            ->from(route('invoices.show', $invoice))
            ->patch(route('invoices.cancel', $invoice))
            ->assertRedirect(route('invoices.show', $invoice))
            ->assertSessionHasErrors('invoice');

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'partial',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 3,
        ]);
    }

    public function test_purchase_with_payment_history_cannot_be_cancelled(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create(['name' => 'Main Supplier']);
        $product = $this->product('Paid Purchase Product', 10);

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-PAID-HISTORY',
            'purchase_date' => '2026-06-07',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 400,
            'remaining_amount' => 600,
            'status' => 'partial',
        ]);

        $purchase->items()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'purchase_price' => 100,
            'line_total' => 1000,
        ]);

        $purchase->payments()->create([
            'supplier_id' => $supplier->id,
            'amount' => 400,
            'payment_method' => 'cash',
            'payment_date' => '2026-06-07',
        ]);

        $this
            ->from(route('purchases.show', $purchase))
            ->post(route('purchases.cancel', $purchase))
            ->assertRedirect(route('purchases.show', $purchase))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'status' => 'partial',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 10,
        ]);
    }

    public function test_invoice_delete_route_is_not_exposed(): void
    {
        $this->assertFalse(Route::has('invoices.destroy'));
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

        Role::findOrCreate('owner');
        $user->assignRole('owner');

        return $user;
    }

    private function product(string $name, int $stock): Product
    {
        return Product::create([
            'name' => $name,
            'sku' => uniqid('SKU-'),
            'purchase_price' => 80,
            'selling_price' => 100,
            'stock_quantity' => $stock,
            'low_stock_alert' => 2,
        ]);
    }
}
