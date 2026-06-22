<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\PurchaseService;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_a_category(): void
    {
        [, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $this->put(route('categories.update', $category), [
            'name' => 'Updated Category',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Updated Category',
        ]);
    }

    public function test_owner_can_manage_a_safe_category_hierarchy(): void
    {
        [, $user] = $this->catalogUser();
        $this->actingAs($user);

        $parentResponse = $this->postJson(route('categories.store'), [
            'name' => 'Tools',
            'parent_id' => null,
        ])->assertCreated();
        $parentId = $parentResponse->json('id');

        $childResponse = $this->postJson(route('categories.store'), [
            'name' => 'Power Tools',
            'parent_id' => $parentId,
        ])->assertCreated()
            ->assertJson(['parent_id' => $parentId]);
        $childId = $childResponse->json('id');

        $this->putJson(route('categories.update', $parentId), [
            'name' => 'Tools',
            'parent_id' => $childId,
        ])->assertUnprocessable()
            ->assertJson([
                'message' => 'A category cannot be placed under one of its own subcategories.',
            ]);

        $this->deleteJson(route('categories.destroy', $parentId))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Move or delete this category\'s subcategories first.',
            ]);

        $this->deleteJson(route('categories.destroy', $childId))->assertOk();
        $this->deleteJson(route('categories.destroy', $parentId))->assertOk();
    }

    public function test_owner_can_manage_brands_from_the_product_form(): void
    {
        [$tenant, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $response = $this->postJson(route('brands.store'), [
            'name' => 'Northstar',
            'description' => 'Hardware and power tools.',
            'is_active' => 1,
        ])->assertCreated()
            ->assertJson([
                'name' => 'Northstar',
                'description' => 'Hardware and power tools.',
                'is_active' => true,
            ]);

        $brand = Brand::findOrFail($response->json('id'));

        $this->putJson(route('brands.update', $brand), [
            'name' => 'Northstar Pro',
            'description' => 'Professional hardware tools.',
            'is_active' => 0,
        ])->assertOk()
            ->assertJson([
                'name' => 'Northstar Pro',
                'is_active' => false,
            ]);

        $this->deleteJson(route('brands.destroy', $brand))->assertOk();

        $usedBrand = Brand::create([
            'tenant_id' => $tenant->id,
            'name' => 'Protected Brand',
            'slug' => 'protected-brand',
            'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->id,
            'brand_id' => $usedBrand->id,
            'name' => 'Branded Product',
            'sku' => 'BRANDED-001',
            'purchase_price' => 10,
            'selling_price' => 15,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);

        $this->deleteJson(route('brands.destroy', $usedBrand))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'This brand is used by products and cannot be deleted.',
            ]);
    }

    public function test_owner_can_create_a_single_product_with_an_image(): void
    {
        Storage::fake('public');
        [, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $this->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Image Product',
            'sku' => 'IMAGE-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 2,
            'images' => [UploadedFile::fake()->createWithContent(
                'product.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl2nS8AAAAASUVORK5CYII=')
            )],
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'IMAGE-001')->firstOrFail();
        $image = $product->images()->firstOrFail();

        Storage::disk('public')->assertExists($image->path);
        $this->assertTrue($image->is_primary);
    }

    public function test_owner_can_create_a_described_inventory_unit(): void
    {
        [, $user] = $this->catalogUser();
        $this->actingAs($user);

        $this->postJson(route('units.store'), [
            'name' => 'Bundle',
            'symbol' => 'bdl',
            'description' => 'A supplier bundle containing several individual items.',
        ])->assertCreated()
            ->assertJson([
                'name' => 'Bundle',
                'symbol' => 'bdl',
                'description' => 'A supplier bundle containing several individual items.',
                'label' => 'Bundle (bdl) - A supplier bundle containing several individual items.',
            ]);

        $this->assertDatabaseHas('units', [
            'name' => 'Bundle',
            'symbol' => 'bdl',
            'description' => 'A supplier bundle containing several individual items.',
        ]);
    }

    public function test_owner_can_update_an_inventory_unit(): void
    {
        [$tenant, $user] = $this->catalogUser();
        $this->actingAs($user);
        $unit = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Bottle',
            'symbol' => 'btl',
            'description' => 'Original description.',
        ]);

        $this->putJson(route('units.update', $unit), [
            'name' => 'Bottle',
            'symbol' => 'bot',
            'description' => 'Used for products purchased and sold by bottle.',
        ])->assertOk()
            ->assertJson([
                'id' => $unit->id,
                'name' => 'Bottle',
                'symbol' => 'bot',
                'description' => 'Used for products purchased and sold by bottle.',
            ]);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'symbol' => 'bot',
            'description' => 'Used for products purchased and sold by bottle.',
        ]);
    }

    public function test_owner_can_delete_only_an_unused_inventory_unit(): void
    {
        [$tenant, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $unusedUnit = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unused Unit',
            'symbol' => 'unused',
            'description' => 'Safe to delete.',
        ]);

        $this->deleteJson(route('units.destroy', $unusedUnit))
            ->assertOk()
            ->assertJson(['message' => 'Unit deleted successfully.']);
        $this->assertDatabaseMissing('units', ['id' => $unusedUnit->id]);

        $usedUnit = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Used Unit',
            'symbol' => 'used',
            'description' => 'Assigned to a product variant.',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Unit Protected Product',
            'sku' => 'UNIT-PROTECTED',
            'purchase_price' => 10,
            'selling_price' => 15,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);
        $product->variants()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Default',
            'sku' => 'UNIT-PROTECTED',
            'unit_id' => $usedUnit->id,
            'purchase_unit_id' => $usedUnit->id,
            'purchase_unit_factor' => 1,
            'purchase_price' => 10,
            'selling_price' => 15,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->deleteJson(route('units.destroy', $usedUnit))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'This unit is currently used by a product or purchase and cannot be deleted.',
            ]);
        $this->assertDatabaseHas('units', ['id' => $usedUnit->id]);
    }

    public function test_owner_can_create_a_brand_attribute_and_multi_variant_product(): void
    {
        [$tenant, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $this->post(route('brands.store'), [
            'name' => 'Northstar',
            'description' => 'Hardware brand',
            'is_active' => 1,
        ])->assertRedirect();

        $this->post(route('product-attributes.store'), [
            'name' => 'Color',
            'values' => 'Black, Blue',
            'is_active' => 1,
        ])->assertRedirect();

        $brandId = $tenant->brands()->value('id');
        $valueIds = \App\Models\ProductAttribute::firstOrFail()->values()->pluck('id')->all();
        $unit = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Piece',
            'symbol' => 'pc',
        ]);

        $this->post(route('products.store'), [
            'category_id' => $category->id,
            'brand_id' => $brandId,
            'name' => 'Cordless Drill',
            'sku' => 'DRILL-12',
            'barcode' => 'DRILL-12-BAR',
            'purchase_price' => 5000,
            'selling_price' => 6500,
            'stock_quantity' => 4,
            'low_stock_alert' => 2,
            'is_active' => 1,
            'is_online_enabled' => 1,
            'variants' => [
                [
                    'name' => '12V Black',
                    'sku' => 'DRILL-12',
                    'barcode' => 'DRILL-12-BAR',
                    'unit_id' => $unit->id,
                    'purchase_unit_id' => $unit->id,
                    'purchase_unit_factor' => 1,
                    'purchase_price' => 5000,
                    'selling_price' => 6500,
                    'stock_quantity' => 4,
                    'low_stock_alert' => 2,
                    'attribute_value_ids' => [$valueIds[0]],
                ],
                [
                    'name' => '18V Blue',
                    'sku' => 'DRILL-18',
                    'barcode' => 'DRILL-18-BAR',
                    'unit_id' => $unit->id,
                    'purchase_unit_id' => $unit->id,
                    'purchase_unit_factor' => 1,
                    'purchase_price' => 7000,
                    'selling_price' => 9000,
                    'stock_quantity' => 6,
                    'low_stock_alert' => 2,
                    'attribute_value_ids' => [$valueIds[1]],
                ],
            ],
        ])->assertRedirect(route('products.index'));

        $product = Product::where('name', 'Cordless Drill')->firstOrFail();

        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertTrue($product->fresh()->has_variants);
        $this->assertTrue($product->fresh()->is_online_enabled);
    }

    public function test_attribute_manager_supports_json_create_update_and_delete(): void
    {
        [, $user] = $this->catalogUser();
        $this->actingAs($user);

        $create = $this->postJson(route('product-attributes.store'), [
            'name' => 'Flavor',
            'values' => 'Cola, Orange',
            'is_active' => 1,
        ])->assertCreated()
            ->assertJsonPath('name', 'Flavor')
            ->assertJsonCount(2, 'values');

        $attributeId = $create->json('id');

        $this->putJson(route('product-attributes.update', $attributeId), [
            'name' => 'Drink Flavor',
            'values' => 'Cola, Orange, Lemon',
            'is_active' => 1,
        ])->assertOk()
            ->assertJsonPath('name', 'Drink Flavor')
            ->assertJsonCount(3, 'values');

        $this->deleteJson(route('product-attributes.destroy', $attributeId))
            ->assertOk()
            ->assertJsonPath('message', 'Product attribute deleted successfully.');

        $this->assertDatabaseMissing('product_attributes', ['id' => $attributeId]);
    }

    public function test_invoice_reduces_only_the_selected_variant_and_updates_product_stock(): void
    {
        [, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Walk-in Customer']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Paint',
            'sku' => 'PAINT-RED',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 12,
            'low_stock_alert' => 2,
        ]);
        $red = $product->variants()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Red',
            'sku' => 'PAINT-RED',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 2,
            'is_default' => true,
        ]);
        $blue = $product->variants()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Blue',
            'sku' => 'PAINT-BLUE',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 7,
            'low_stock_alert' => 2,
        ]);

        $this->post(route('invoices.store'), [
            'invoice_no' => 'INV-VARIANT-1',
            'sale_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'products' => [[
                'product_id' => $product->id,
                'product_variant_id' => $blue->id,
                'quantity' => 2,
                'price' => 150,
            ]],
        ])->assertRedirect(route('invoices.index'));

        $this->assertSame(5, $red->fresh()->stock_quantity);
        $this->assertSame(5, $blue->fresh()->stock_quantity);
        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('invoice_items', [
            'product_id' => $product->id,
            'product_variant_id' => $blue->id,
            'quantity' => 2,
        ]);
    }

    public function test_barcode_lookup_returns_the_exact_variant(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureTenantFeatureIsEnabled::class);
        [, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Tablets',
            'sku' => 'TAB-10',
            'purchase_price' => 50,
            'selling_price' => 80,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
        ]);
        $variant = $product->variants()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Pack of 20',
            'sku' => 'TAB-20',
            'barcode' => 'TAB-PACK-20',
            'purchase_price' => 90,
            'selling_price' => 140,
            'stock_quantity' => 3,
            'low_stock_alert' => 1,
            'is_default' => true,
        ]);

        $this->postJson(route('barcode.lookup'), ['barcode' => 'TAB-PACK-20'])
            ->assertOk()
            ->assertJson([
                'id' => $product->id,
                'product_variant_id' => $variant->id,
                'name' => 'Tablets - Pack of 20',
                'stock_quantity' => 3,
            ]);
    }

    public function test_purchase_unit_conversion_adds_base_units_to_stock(): void
    {
        [$tenant, $user, $category] = $this->catalogUser();
        $this->actingAs($user);

        $piece = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Piece',
            'symbol' => 'pc',
        ]);
        $box = Unit::create([
            'tenant_id' => $tenant->id,
            'name' => 'Box',
            'symbol' => 'box',
        ]);
        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Unit Supplier',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Boxed Item',
            'sku' => 'BOXED-ITEM',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
        ]);
        $variant = $product->variants()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Default',
            'sku' => 'BOXED-ITEM',
            'unit_id' => $piece->id,
            'purchase_unit_id' => $box->id,
            'purchase_unit_factor' => 12,
            'purchase_unit_price' => 1200,
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
            'is_default' => true,
            'is_active' => true,
        ]);

        app(PurchaseService::class)->store([
            'supplier_id' => $supplier->id,
            'purchase_no' => 'PUR-UNIT-001',
            'purchase_date' => now()->toDateString(),
            'subtotal' => 2400,
            'total' => 2400,
            'paid_amount' => 0,
            'products' => [[
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 2,
                'purchase_price' => 1200,
            ]],
        ]);

        $this->assertSame(24, $variant->fresh()->stock_quantity);
        $this->assertEquals(100, $variant->fresh()->purchase_price);
        $this->assertDatabaseHas('purchase_items', [
            'product_variant_id' => $variant->id,
            'unit_id' => $box->id,
            'quantity' => 2,
            'base_quantity' => 24,
            'unit_factor' => 12,
            'purchase_price' => 1200,
        ]);
    }

    private function catalogUser(): array
    {
        $tenant = Tenant::create([
            'name' => 'Catalog Store',
            'slug' => uniqid('catalog-store-'),
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);
        $category = Category::create(['name' => 'General']);

        return [$tenant, $user, $category];
    }
}
