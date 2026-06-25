<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSerialNumber;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\ProductCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSaleModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_loose_product_can_be_sold_in_decimal_quantity(): void
    {
        [, $user] = $this->storeUser('Loose Store');
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Walk-in Customer']);
        $kg = Unit::create(['name' => 'Kilogram', 'symbol' => 'kg']);
        $bag = Unit::create(['name' => 'Bag', 'symbol' => 'bag']);
        $product = $this->catalogProduct([
            'name' => 'Loose Rice',
            'sku' => 'RICE-LOOSE',
            'product_sale_mode' => Product::SALE_MODE_LOOSE,
            'allow_loose_sale' => true,
            'base_stock_unit_id' => $kg->id,
            'default_purchase_unit_id' => $bag->id,
            'default_purchase_unit_factor' => 50,
            'variants' => [[
                'name' => 'Default',
                'sku' => 'RICE-LOOSE',
                'unit_id' => $kg->id,
                'purchase_unit_id' => $bag->id,
                'purchase_unit_factor' => 50,
                'purchase_price' => 12000,
                'selling_price' => 280,
                'stock_quantity' => 10,
                'low_stock_alert' => 2,
                'is_active' => true,
            ]],
        ]);
        $variant = $product->variants()->firstOrFail();

        $this->post(route('invoices.store'), $this->invoicePayload($customer, $product, $variant, 0.250, 280))
            ->assertRedirect(route('invoices.index'));

        $this->assertSame(9.75, $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $product->id,
            'quantity' => 0.250,
        ]);
    }

    public function test_service_product_does_not_create_stock_movement(): void
    {
        [, $user] = $this->storeUser('Service Store');
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Service Customer']);
        $piece = Unit::create(['name' => 'Service Unit', 'symbol' => 'svc']);
        $product = $this->catalogProduct([
            'name' => 'Delivery Charge',
            'sku' => 'SERVICE-DELIVERY',
            'product_sale_mode' => Product::SALE_MODE_SERVICE,
            'variants' => [[
                'name' => 'Default',
                'sku' => 'SERVICE-DELIVERY',
                'unit_id' => $piece->id,
                'purchase_unit_id' => $piece->id,
                'purchase_unit_factor' => 1,
                'purchase_price' => 0,
                'selling_price' => 150,
                'stock_quantity' => 0,
                'low_stock_alert' => 0,
                'track_stock' => false,
                'is_active' => true,
            ]],
        ]);
        $variant = $product->variants()->firstOrFail();

        $this->post(route('invoices.store'), $this->invoicePayload($customer, $product, $variant, 1, 150))
            ->assertRedirect(route('invoices.index'));

        $this->assertSame(0, StockMovement::count());
        $this->assertSame(0, $variant->fresh()->stock_quantity);
    }

    public function test_serialized_product_requires_available_serial_number(): void
    {
        [, $user] = $this->storeUser('Serial Store');
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Serial Customer']);
        $piece = Unit::create(['name' => 'Piece', 'symbol' => 'pc']);
        $product = $this->catalogProduct([
            'name' => 'Mobile Phone',
            'sku' => 'PHONE-001',
            'product_sale_mode' => Product::SALE_MODE_SERIALIZED,
            'track_serial' => true,
            'variants' => [[
                'name' => 'Default',
                'sku' => 'PHONE-001',
                'unit_id' => $piece->id,
                'purchase_unit_id' => $piece->id,
                'purchase_unit_factor' => 1,
                'purchase_price' => 20000,
                'selling_price' => 25000,
                'stock_quantity' => 2,
                'low_stock_alert' => 0,
                'is_active' => true,
            ]],
        ]);
        $variant = $product->variants()->firstOrFail();

        ProductSerialNumber::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'serial_number' => 'SN-001',
            'status' => ProductSerialNumber::STATUS_AVAILABLE,
        ]);

        $this->post(route('invoices.store'), $this->invoicePayload($customer, $product, $variant, 1, 25000, [
            'serial_numbers' => 'SN-001',
        ]))->assertRedirect(route('invoices.index'));

        $this->assertSame(ProductSerialNumber::STATUS_SOLD, ProductSerialNumber::first()->status);

        $this->post(route('invoices.store'), $this->invoicePayload($customer, $product, $variant, 1, 25000, [
            'invoice_no' => 'INV-SERIAL-2',
            'serial_numbers' => 'SN-001',
        ]))->assertSessionHasErrors('products');

        $invoice = Invoice::where('invoice_no', 'INV-MODE-1')->firstOrFail();

        $this->patch(route('invoices.cancel', $invoice))
            ->assertRedirect(route('invoices.index'));

        $this->assertSame(ProductSerialNumber::STATUS_AVAILABLE, ProductSerialNumber::first()->fresh()->status);
    }

    public function test_batch_tracked_sale_deducts_earliest_expiring_batch_first(): void
    {
        [, $user] = $this->storeUser('Batch Store');
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Batch Customer']);
        $piece = Unit::create(['name' => 'Piece', 'symbol' => 'pc']);
        $product = $this->catalogProduct([
            'name' => 'Baby Formula',
            'sku' => 'FORMULA-001',
            'product_sale_mode' => Product::SALE_MODE_BATCH_TRACKED,
            'track_batch' => true,
            'track_expiry' => true,
            'variants' => [[
                'name' => 'Default',
                'sku' => 'FORMULA-001',
                'unit_id' => $piece->id,
                'purchase_unit_id' => $piece->id,
                'purchase_unit_factor' => 1,
                'purchase_price' => 1000,
                'selling_price' => 1300,
                'stock_quantity' => 10,
                'low_stock_alert' => 2,
                'is_active' => true,
            ]],
        ]);
        $variant = $product->variants()->firstOrFail();
        $early = ProductBatch::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'batch_number' => 'EARLY',
            'expiry_date' => now()->addDays(5)->toDateString(),
            'quantity' => 3,
        ]);
        $later = ProductBatch::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'batch_number' => 'LATER',
            'expiry_date' => now()->addDays(40)->toDateString(),
            'quantity' => 7,
        ]);

        $this->post(route('invoices.store'), $this->invoicePayload($customer, $product, $variant, 4, 1300))
            ->assertRedirect(route('invoices.index'));

        $this->assertEquals(0, $early->fresh()->quantity);
        $this->assertEquals(6, $later->fresh()->quantity);
    }

    public function test_batch_tracked_sale_can_deduct_selected_batch(): void
    {
        [, $user] = $this->storeUser('Selected Batch Store');
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Selected Batch Customer']);
        $piece = Unit::create(['name' => 'Piece', 'symbol' => 'pc']);
        $product = $this->catalogProduct([
            'name' => 'Medicine Syrup',
            'sku' => 'SYRUP-001',
            'product_sale_mode' => Product::SALE_MODE_BATCH_TRACKED,
            'track_batch' => true,
            'track_expiry' => true,
            'variants' => [[
                'name' => 'Default',
                'sku' => 'SYRUP-001',
                'unit_id' => $piece->id,
                'purchase_unit_id' => $piece->id,
                'purchase_unit_factor' => 1,
                'purchase_price' => 200,
                'selling_price' => 260,
                'stock_quantity' => 8,
                'low_stock_alert' => 2,
                'is_active' => true,
            ]],
        ]);
        $variant = $product->variants()->firstOrFail();
        $first = ProductBatch::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'batch_number' => 'BATCH-A',
            'expiry_date' => now()->addDays(5)->toDateString(),
            'quantity' => 5,
        ]);
        $selected = ProductBatch::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'batch_number' => 'BATCH-B',
            'expiry_date' => now()->addDays(20)->toDateString(),
            'quantity' => 3,
        ]);

        $this->post(route('invoices.store'), $this->invoicePayload($customer, $product, $variant, 2, 260, [
            'batch_number' => 'BATCH-B',
        ]))->assertRedirect(route('invoices.index'));

        $this->assertEquals(5, $first->fresh()->quantity);
        $this->assertEquals(1, $selected->fresh()->quantity);
    }

    public function test_expiry_alerts_and_report_show_near_expiry_batches(): void
    {
        [, $user] = $this->storeUser('Expiry Store');
        $this->actingAs($user);

        $piece = Unit::create(['name' => 'Piece', 'symbol' => 'pc']);
        $product = $this->catalogProduct([
            'name' => 'Expiry Product',
            'sku' => 'EXP-001',
            'product_sale_mode' => Product::SALE_MODE_BATCH_TRACKED,
            'track_batch' => true,
            'track_expiry' => true,
            'variants' => [[
                'name' => 'Default',
                'sku' => 'EXP-001',
                'unit_id' => $piece->id,
                'purchase_unit_id' => $piece->id,
                'purchase_unit_factor' => 1,
                'purchase_price' => 100,
                'selling_price' => 150,
                'stock_quantity' => 2,
                'low_stock_alert' => 1,
                'is_active' => true,
            ]],
        ]);
        $variant = $product->variants()->firstOrFail();

        ProductBatch::create([
            'tenant_id' => $user->tenant_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'batch_number' => 'EXP-SOON',
            'expiry_date' => now()->addDays(7)->toDateString(),
            'quantity' => 2,
        ]);

        $this->get(route('alerts.index'))
            ->assertOk()
            ->assertSee('Expiry Alerts')
            ->assertSee('EXP-SOON');

        $this->get(route('reports.expiring-stock'))
            ->assertOk()
            ->assertSee('Expiring Stock Report')
            ->assertSee('EXP-SOON');
    }

    private function storeUser(string $storeName): array
    {
        $tenant = Tenant::create([
            'name' => $storeName,
            'slug' => uniqid(str($storeName)->slug().'-'),
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        return [$tenant, $user];
    }

    private function catalogProduct(array $overrides): Product
    {
        $category = Category::create(['name' => ($overrides['name'] ?? 'Product').' Category']);

        return app(ProductCatalogService::class)->create(array_replace_recursive([
            'category_id' => $category->id,
            'name' => 'Product',
            'sku' => 'PRODUCT-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 0,
            'low_stock_alert' => 0,
            'is_active' => true,
            'is_online_enabled' => false,
            'variants' => [],
        ], $overrides));
    }

    private function invoicePayload(Customer $customer, Product $product, $variant, float $quantity, float $price, array $extra = []): array
    {
        return [
            'invoice_no' => $extra['invoice_no'] ?? 'INV-MODE-1',
            'sale_date' => '2026-06-23',
            'customer_id' => $customer->id,
            'tax' => 0,
            'discount' => 0,
            'extra_expense' => 0,
            'paid_amount' => 0,
            'products' => [[
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'price' => $price,
                'batch_number' => $extra['batch_number'] ?? null,
                'serial_numbers' => $extra['serial_numbers'] ?? null,
            ]],
        ];
    }
}
