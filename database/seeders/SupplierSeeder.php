<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
   public function run(): void
    {
        foreach (Tenant::all() as $tenant) {

            Supplier::create([
                'tenant_id' => $tenant->id,
                'name' => 'Default Supplier',
                'phone' => '000000000',
                'email' => 'supplier@test.com',
                'address' => 'N/A',
            ]);
        }
    }
}
