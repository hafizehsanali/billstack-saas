<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
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
            ->assertSee('Rs 1,100.00')
            ->assertSee('Healthy Product')
            ->assertSee('Barcode: 123456')
            ->assertSee('In Stock')
            ->assertSee('Low Stock')
            ->assertSee('Out of Stock')
            ->assertSee('Stock Ledger');
    }
}
