<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {

            Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'Walk-in Customer',
                'phone' => '03001234567',
                'email' => 'walkin@example.com',
                'address' => 'Chakwal',
                'opening_balance' => 0,
            ]);

            Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'John Trading Company',
                'phone' => '03111234567',
                'email' => 'john@example.com',
                'address' => 'Lahore',
                'opening_balance' => 5000,
            ]);
        }
    }
}
