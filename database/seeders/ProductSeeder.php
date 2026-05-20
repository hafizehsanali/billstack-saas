<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Category;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {

            $categories = Category::where(
                'tenant_id',
                $tenant->id
            )->get();

            Product::create([
                'tenant_id' => $tenant->id,
                'category_id' => $categories->first()->id,
                'name' => 'Laptop',
                'sku' => 'LAP-001',
                'purchase_price' => 80000,
                'selling_price' => 95000,
                'stock_quantity' => 15,
                'low_stock_alert' => 5,
            ]);
             Product::create([
                'tenant_id' => $tenant->id,
                'category_id' => $categories[1]->id,
                'name' => 'Wireless Mouse',
                'sku' => 'MOU-001',
                'purchase_price' => 1000,
                'selling_price' => 1800,
                'stock_quantity' => 30,
                'low_stock_alert' => 10,
            ]);

            Product::create([
                'tenant_id' => $tenant->id,
                'category_id' => $categories[2]->id,
                'name' => 'Notebook',
                'sku' => 'NOTE-001',
                'purchase_price' => 100,
                'selling_price' => 180,
                'stock_quantity' => 50,
                'low_stock_alert' => 15,
            ]);
        }
    }
}
