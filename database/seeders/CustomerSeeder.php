<?php

namespace Database\Seeders;

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
                'email' => 'walkin@example.test',
                'address' => 'Chakwal',
                'opening_balance' => 0,
            ]);

            Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'Crescent Trading Company',
                'phone' => '03111234567',
                'email' => 'crescent@example.test',
                'address' => 'Lahore',
                'opening_balance' => 5000,
            ]);

            Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'Mushtaq Retail Mart',
                'phone' => '03001234567',
                'email' => 'mushtaq@example.test',
                'address' => 'Talagang',
                'opening_balance' => 0,
            ]);

            Customer::create([
                'tenant_id' => $tenant->id,
                'name' => 'Hafiz Brothers Trading Company',
                'phone' => '03111234567',
                'email' => 'hafizbrothers@example.test',
                'address' => 'Islamabad',
                'opening_balance' => 5000,
            ]);
        }
    }
}
