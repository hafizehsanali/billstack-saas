<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::create([
            'name' => 'Demo Company 1',
            'slug' => 'demo-store-1',
        ]);

        Tenant::create([
            'name' => 'Demo Company 2',
            'slug' => 'demo-store-2',
        ]);

        Tenant::create([
            'name' => 'Demo Company 3',
            'slug' => 'demo-store-3',
        ]);
    }
}
