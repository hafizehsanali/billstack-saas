<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::create([
            'name' => 'Zephrant General Store Demo',
            'slug' => 'demo-store-1',
        ]);

        Tenant::create([
            'name' => 'Summit Hardware',
            'slug' => 'demo-store-2',
        ]);

        Tenant::create([
            'name' => 'Greenline Pharmacy',
            'slug' => 'demo-store-3',
        ]);
    }
}
