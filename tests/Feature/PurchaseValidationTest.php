<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_paid_amount_cannot_be_greater_than_purchase_total(): void
    {
        [$tenant, $user] = $this->createOwnerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Supplier',
        ]);

        $product = $this->createProduct('Hammer', 'HAM-001');

        $this
            ->from(route('purchases.create'))
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchase_no' => 'PUR-OVERPAID',
                'purchase_date' => '2026-06-04',
                'subtotal' => 100,
                'extra_expense' => 0,
                'discount' => 0,
                'paid_amount' => 101,
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'purchase_price' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHasErrors('paid_amount')
            ->assertSessionHasInput('purchase_no', 'PUR-OVERPAID')
            ->assertSessionHasInput('paid_amount', 101);

        $this->assertDatabaseMissing('purchases', [
            'purchase_no' => 'PUR-OVERPAID',
        ]);
    }

    public function test_purchase_supplier_and_products_must_belong_to_current_store(): void
    {
        [$firstTenant, $firstUser] = $this->createOwnerUser('First Store');
        [$secondTenant, $secondUser] = $this->createOwnerUser('Second Store');

        $this->actingAs($secondUser);

        $foreignSupplier = Supplier::create([
            'tenant_id' => $secondTenant->id,
            'name' => 'Foreign Supplier',
        ]);

        $foreignProduct = $this->createProduct('Foreign Product', 'FOR-001');

        $this->actingAs($firstUser);

        $this
            ->from(route('purchases.create'))
            ->post(route('purchases.store'), [
                'supplier_id' => $foreignSupplier->id,
                'purchase_no' => 'PUR-FOREIGN',
                'purchase_date' => '2026-06-04',
                'subtotal' => 100,
                'extra_expense' => 0,
                'discount' => 0,
                'paid_amount' => 0,
                'products' => [
                    [
                        'product_id' => $foreignProduct->id,
                        'quantity' => 1,
                        'purchase_price' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHasErrors([
                'supplier_id',
                'products.0.product_id',
            ]);

        $this->assertDatabaseMissing('purchases', [
            'tenant_id' => $firstTenant->id,
            'purchase_no' => 'PUR-FOREIGN',
        ]);
    }

    public function test_purchase_can_be_updated_with_product_rows_from_edit_form(): void
    {
        [$tenant, $user] = $this->createOwnerUser();
        $this->actingAs($user);

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Supplier',
        ]);

        $product = $this->createProduct('Paint', 'PNT-001');

        $purchase = Purchase::create([
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-EDIT',
            'purchase_date' => '2026-06-03',
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 0,
            'remaining_amount' => 100,
            'status' => 'unpaid',
        ]);

        $purchase->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'purchase_price' => 100,
            'line_total' => 100,
        ]);

        $this
            ->put(route('purchases.update', $purchase), [
                'supplier_id' => $supplier->id,
                'purchase_no' => 'PUR-EDIT',
                'purchase_date' => '2026-06-04',
                'subtotal' => 200,
                'extra_expense' => 0,
                'discount' => 0,
                'total' => 200,
                'paid_amount' => 50,
                'remaining_amount' => 150,
                'status' => 'partial',
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'purchase_price' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('purchases.index'));

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'subtotal' => 200,
            'total' => 200,
            'paid_amount' => 50,
            'remaining_amount' => 150,
            'status' => 'partial',
        ]);

        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'purchase_price' => 100,
            'line_total' => 200,
        ]);
    }

    public function test_purchase_number_must_be_unique_only_within_current_store(): void
    {
        [, $firstUser] = $this->createOwnerUser('First Store');
        [, $secondUser] = $this->createOwnerUser('Second Store');

        $this->actingAs($secondUser);

        $secondSupplier = Supplier::create([
            'tenant_id' => $secondUser->tenant_id,
            'name' => 'Second Store Supplier',
        ]);

        Purchase::create([
            'tenant_id' => $secondUser->tenant_id,
            'supplier_id' => $secondSupplier->id,
            'purchase_no' => 'PUR-SHARED',
            'purchase_date' => '2026-06-03',
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 0,
            'remaining_amount' => 100,
            'status' => 'unpaid',
        ]);

        $this->actingAs($firstUser);

        $supplier = Supplier::create([
            'tenant_id' => $firstUser->tenant_id,
            'name' => 'First Store Supplier',
        ]);

        $product = $this->createProduct('First Store Purchase Product', 'FIRST-PUR-001');

        $this
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchase_no' => 'PUR-SHARED',
                'purchase_date' => '2026-06-04',
                'subtotal' => 100,
                'extra_expense' => 0,
                'discount' => 0,
                'paid_amount' => 0,
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'purchase_price' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('purchases.index'));

        $this->assertDatabaseHas('purchases', [
            'tenant_id' => $firstUser->tenant_id,
            'purchase_no' => 'PUR-SHARED',
        ]);

        $this
            ->from(route('purchases.create'))
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchase_no' => 'PUR-SHARED',
                'purchase_date' => '2026-06-05',
                'subtotal' => 100,
                'extra_expense' => 0,
                'discount' => 0,
                'paid_amount' => 0,
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'purchase_price' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('purchases.create'))
            ->assertSessionHasErrors('purchase_no');
    }

    private function createOwnerUser(string $storeName = 'Demo Store'): array
    {
        $tenant = Tenant::create([
            'name' => $storeName,
            'slug' => uniqid(str($storeName)->slug().'-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Role::findOrCreate('owner');
        $user->assignRole('owner');

        return [$tenant, $user];
    }

    private function createProduct(string $name, string $sku): Product
    {
        $category = Category::create([
            'name' => $name.' Category',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'sku' => $sku,
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 2,
        ]);
    }
}
