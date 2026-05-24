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
        foreach (Tenant::all() as $index => $tenant) {

            
            Supplier::create([
                'tenant_id' => $tenant->id,

                'name' => 'Supplier '.$index.' of store '. $tenant->id,

                'phone' => '03000000000',

                'email' => 'supplier'.$index.'@test.com',

                'address' => 'Demo Address of '.$tenant->name.' Supplier',
            ]);
        }
    }
}
