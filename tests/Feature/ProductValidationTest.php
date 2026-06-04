<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_barcode_must_be_unique_within_store(): void
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
            'name' => 'Existing Product',
            'sku' => 'EXIST-001',
            'barcode' => '123456',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 2,
        ]);

        $this
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'category_id' => $category->id,
                'name' => 'Duplicate Barcode Product',
                'sku' => 'NEW-001',
                'barcode' => '123456',
                'purchase_price' => 100,
                'selling_price' => 150,
                'stock_quantity' => 5,
                'low_stock_alert' => 2,
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('barcode');
    }

    public function test_product_category_must_belong_to_current_store(): void
    {
        $firstTenant = Tenant::create([
            'name' => 'First Store',
            'slug' => uniqid('first-store-'),
        ]);

        $secondTenant = Tenant::create([
            'name' => 'Second Store',
            'slug' => uniqid('second-store-'),
        ]);

        $secondUser = User::factory()->create([
            'tenant_id' => $secondTenant->id,
        ]);

        $this->actingAs($secondUser);

        $foreignCategory = Category::create([
            'name' => 'Foreign Category',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $firstTenant->id,
        ]);

        $this->actingAs($user);

        $this
            ->from(route('products.create'))
            ->post(route('products.store'), [
                'category_id' => $foreignCategory->id,
                'name' => 'Wrong Store Product',
                'sku' => 'WRONG-001',
                'purchase_price' => 100,
                'selling_price' => 150,
                'stock_quantity' => 5,
                'low_stock_alert' => 2,
            ])
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('category_id');
    }
}
