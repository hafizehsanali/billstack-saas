<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tenant;
use Illuminate\Database\Seeder;


class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // DEMO STORE 1
        $tenantDemoStore = Tenant::create([
            'name' => 'Demo Company 1',
            'slug' => 'demo-store-1',
        ]);
        
        // DEMO STORE 2
        $tenant1 = Tenant::create([
            'name' => 'Demo Company 2',
            'slug' => 'demo-store-2',
        ]);

        // DEMO STORE 3
        $tenant1 = Tenant::create([
            'name' => 'Demo Company 3',
            'slug' => 'demo-store-3',
        ]);

       
    }
}
