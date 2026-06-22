<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Services\ProductCatalogService;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_customer_and_products_must_belong_to_current_store(): void
    {
        [$firstTenant, $firstUser] = $this->createUserForStore('First Store');
        [, $secondUser] = $this->createUserForStore('Second Store');

        $this->actingAs($secondUser);

        $foreignCustomer = Customer::create([
            'name' => 'Foreign Customer',
        ]);

        $foreignProduct = $this->createProduct('Foreign Product', 'FOR-INV-001');

        $this->actingAs($firstUser);

        $this
            ->from(route('invoices.create'))
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-FOREIGN',
                'sale_date' => '2026-06-04',
                'customer_id' => $foreignCustomer->id,
                'tax' => 0,
                'discount' => 0,
                'extra_expense' => 0,
                'paid_amount' => 0,
                'products' => [
                    [
                        'product_id' => $foreignProduct->id,
                        'quantity' => 1,
                        'price' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors([
                'customer_id',
                'products.0.product_id',
            ]);

        $this->assertDatabaseMissing('invoices', [
            'tenant_id' => $firstTenant->id,
            'invoice_no' => 'INV-FOREIGN',
        ]);
    }

    public function test_invoice_number_must_be_unique_only_within_current_store(): void
    {
        [$firstTenant, $firstUser] = $this->createUserForStore('First Store');
        [, $secondUser] = $this->createUserForStore('Second Store');

        $this->actingAs($secondUser);

        $secondCustomer = Customer::create([
            'name' => 'Second Store Customer',
        ]);

        Invoice::create([
            'tenant_id' => $secondUser->tenant_id,
            'customer_id' => $secondCustomer->id,
            'invoice_no' => 'INV-SHARED',
            'sale_date' => '2026-06-03',
            'subtotal' => 100,
            'total' => 100,
            'paid_amount' => 0,
            'remaining_amount' => 100,
            'status' => 'unpaid',
        ]);

        $this->actingAs($firstUser);

        $customer = Customer::create([
            'name' => 'First Store Customer',
        ]);

        $product = $this->createProduct('First Store Product', 'FIRST-INV-001');

        $this
            ->post(route('invoices.store'), [
                'invoice_no' => 'INV-SHARED',
                'sale_date' => '2026-06-04',
                'customer_id' => $customer->id,
                'tax' => 0,
                'discount' => 0,
                'extra_expense' => 0,
                'paid_amount' => 0,
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'price' => 100,
                    ],
                ],
            ])
            ->assertRedirect(route('invoices.index'));

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $firstTenant->id,
            'invoice_no' => 'INV-SHARED',
        ]);
    }

    public function test_invoice_snapshots_promotional_item_savings(): void
    {
        [, $user] = $this->createUserForStore('Promotion Store');
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Promotion Customer']);
        $product = $this->createProduct('Promotional Product', 'PROMO-001');
        $variant = app(ProductCatalogService::class)->resolveVariant($product->id);
        $variant->update([
            'selling_price' => 150,
            'compare_at_price' => 200,
        ]);

        $this->post(route('invoices.store'), [
            'invoice_no' => 'INV-PROMO',
            'sale_date' => '2026-06-14',
            'customer_id' => $customer->id,
            'tax' => 0,
            'discount' => 0,
            'extra_expense' => 0,
            'paid_amount' => 0,
            'products' => [[
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'price' => 150,
            ]],
        ])->assertRedirect(route('invoices.index'));

        $invoice = Invoice::where('invoice_no', 'INV-PROMO')->firstOrFail();

        $this->assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id,
            'regular_price' => 200,
            'price' => 150,
            'item_savings' => 100,
            'total' => 300,
        ]);

        $this->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertSee('You saved Rs 100.00')
            ->assertSee('Promotional Savings: Rs 100.00');
    }

    private function createUserForStore(string $storeName): array
    {
        $tenant = Tenant::create([
            'name' => $storeName,
            'slug' => uniqid(str($storeName)->slug().'-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

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
