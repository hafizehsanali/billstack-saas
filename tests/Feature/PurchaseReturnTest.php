<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_return_reduces_stock_and_purchase_balance(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Main Supplier',
        ]);

        $product = Product::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Returnable Product',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-RETURN',
            'purchase_date' => '2026-06-01',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'purchase_price' => 100,
            'line_total' => 1000,
        ]);

        $this
            ->post(route('purchase-returns.store', $purchase), [
                'return_date' => '2026-06-02',
                'items' => [
                    $purchaseItem->id => [
                        'quantity' => 3,
                    ],
                ],
                'notes' => 'Damaged items',
            ])
            ->assertRedirect(route('purchases.show', $purchase));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 7,
        ]);

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'subtotal' => 700,
            'total' => 700,
            'remaining_amount' => 700,
            'status' => 'unpaid',
        ]);

        $this->assertDatabaseHas('purchase_returns', [
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
            'total_amount' => 300,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'purchase_return',
            'direction' => 'out',
            'quantity' => 3,
        ]);
    }

    public function test_supplier_account_includes_purchase_returns_as_credit(): void
    {
        $user = $this->ownerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Main Supplier',
        ]);

        $product = Product::create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Returnable Product',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $user->tenant_id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-LEDGER',
            'purchase_date' => '2026-06-01',
            'subtotal' => 1000,
            'total' => 1000,
            'paid_amount' => 0,
            'remaining_amount' => 1000,
            'status' => 'unpaid',
        ]);

        $purchaseItem = PurchaseItem::create([
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'purchase_price' => 100,
            'line_total' => 1000,
        ]);

        $this->post(route('purchase-returns.store', $purchase), [
            'return_date' => '2026-06-02',
            'items' => [
                $purchaseItem->id => [
                    'quantity' => 3,
                ],
            ],
        ]);

        $this
            ->get(route('supplier.account', $supplier))
            ->assertOk()
            ->assertSee('Supplier Return')
            ->assertSee('300.00');
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
