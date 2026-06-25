<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\BusinessPreset;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $generalStore = BusinessPreset::where('slug', BusinessPreset::GENERAL_STORE)->value('id');
        $hardware = BusinessPreset::where('slug', BusinessPreset::HARDWARE)->value('id');
        $pharmacy = BusinessPreset::where('slug', BusinessPreset::PHARMACY)->value('id');

        Tenant::updateOrCreate(['slug' => 'demo-store-1'], [
            'name' => 'Zephrant General Store Demo',
            'business_preset_id' => $generalStore,
        ]);

        Tenant::updateOrCreate(['slug' => 'demo-store-2'], [
            'name' => 'Summit Hardware',
            'business_preset_id' => $hardware,
        ]);

        Tenant::updateOrCreate(['slug' => 'demo-store-3'], [
            'name' => 'Greenline Pharmacy',
            'business_preset_id' => $pharmacy,
        ]);
    }
}
