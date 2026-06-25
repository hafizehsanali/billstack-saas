<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_shows_inventory_summary_and_stock_statuses(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => uniqid('demo-store-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $category = Category::create([
            'name' => 'Hardware',
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Healthy Product',
            'sku' => 'HP-001',
            'barcode' => '123456',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'low_stock_alert' => 3,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Low Product',
            'sku' => 'LP-001',
            'purchase_price' => 50,
            'selling_price' => 80,
            'stock_quantity' => 2,
            'low_stock_alert' => 5,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Empty Product',
            'sku' => 'EP-001',
            'purchase_price' => 25,
            'selling_price' => 40,
            'stock_quantity' => 0,
            'low_stock_alert' => 2,
        ]);

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Total Products')
            ->assertSee('Low Stock Items')
            ->assertSee('Out of Stock')
            ->assertSee('Stock Value at Cost')
            ->assertSee('Apply Filters')
            ->assertSee('Rs 1,100.00')
            ->assertSee('Healthy Product')
            ->assertSee('Barcode: 123456')
            ->assertSee('In Stock')
            ->assertSee('Low Stock')
            ->assertSee('Out of Stock')
            ->assertSee('Stock Ledger');
    }

    public function test_product_index_can_filter_by_category_brand_and_stock_status(): void
    {
        $tenant = Tenant::create([
            'name' => 'Filter Store',
            'slug' => uniqid('filter-store-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $grocery = Category::create(['name' => 'Groceries']);
        $hardware = Category::create(['name' => 'Hardware']);
        $freshBrand = Brand::create(['name' => 'FreshCo', 'slug' => 'freshco']);
        $toolBrand = Brand::create(['name' => 'ToolPro', 'slug' => 'toolpro']);

        Product::create([
            'category_id' => $grocery->id,
            'brand_id' => $freshBrand->id,
            'name' => 'Basmati Rice',
            'sku' => 'RICE-001',
            'purchase_price' => 200,
            'selling_price' => 280,
            'stock_quantity' => 4,
            'low_stock_alert' => 10,
        ]);

        Product::create([
            'category_id' => $hardware->id,
            'brand_id' => $toolBrand->id,
            'name' => 'Steel Hammer',
            'sku' => 'HAM-001',
            'purchase_price' => 500,
            'selling_price' => 750,
            'stock_quantity' => 30,
            'low_stock_alert' => 5,
        ]);

        $this
            ->get(route('products.index', [
                'category_id' => $grocery->id,
                'brand_id' => $freshBrand->id,
                'stock_status' => 'low_stock',
            ]))
            ->assertOk()
            ->assertSee('Basmati Rice')
            ->assertSee('Low Stock')
            ->assertDontSee('Steel Hammer');
    }

    public function test_unused_product_can_be_deleted_with_its_variants(): void
    {
        $tenant = Tenant::create([
            'name' => 'Delete Store',
            'slug' => uniqid('delete-store-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $category = Category::create(['name' => 'Groceries']);
        $unit = Unit::create(['name' => 'Piece', 'symbol' => 'pc']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Unused Product',
            'slug' => 'unused-product',
            'sku' => 'UNUSED-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 0,
            'low_stock_alert' => 5,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Default',
            'sku' => 'UNUSED-001-DEFAULT',
            'unit_id' => $unit->id,
            'purchase_unit_id' => $unit->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'is_default' => true,
        ]);

        $this
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
    }

    public function test_product_with_stock_history_cannot_be_deleted(): void
    {
        $tenant = Tenant::create([
            'name' => 'Protected Store',
            'slug' => uniqid('protected-store-'),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $this->actingAs($user);

        $category = Category::create(['name' => 'Hardware']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Protected Product',
            'slug' => 'protected-product',
            'sku' => 'PROTECTED-001',
            'purchase_price' => 200,
            'selling_price' => 280,
            'stock_quantity' => 10,
            'low_stock_alert' => 5,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'opening_stock',
            'direction' => 'in',
            'quantity' => 10,
            'stock_after' => 10,
        ]);

        $this
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Protected Product')
            ->assertDontSee('Delete this product?');

        $this
            ->from(route('products.index'))
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'))
            ->assertSessionHasErrors('product');

        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
    }
}
