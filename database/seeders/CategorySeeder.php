<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {

            Category::create([
                'tenant_id' => $tenant->id,
                'name' => 'Electronics',
            ]);

            Category::create([
                'tenant_id' => $tenant->id,
                'name' => 'Accessories',
            ]);

            Category::create([
                'tenant_id' => $tenant->id,
                'name' => 'Office Items',
            ]);

            Category::create([
                'tenant_id' => $tenant->id,
                'name' => 'General',
            ]);

        }
    }
}
