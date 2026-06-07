<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Tenant::all() as $index => $tenant) {
            Supplier::create([
                'tenant_id' => $tenant->id,
                'name' => [
                    'Metro Wholesale Supply',
                    'Prime Tools Distribution',
                    'MediCore Distributors',
                ][$index] ?? 'Regional Wholesale Supplier',
                'phone' => '0300000000'.($index + 1),
                'email' => 'supplier'.($index + 1).'@example.test',
                'address' => 'Commercial Market, '.$tenant->name,
            ]);
        }
    }
}
