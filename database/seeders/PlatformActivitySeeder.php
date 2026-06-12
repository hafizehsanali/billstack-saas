<?php

namespace Database\Seeders;

use App\Models\PlatformActivityLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class PlatformActivitySeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'platform@test.com')->first();
        $tenant = Tenant::where('slug', 'demo-store-2')->first();

        PlatformActivityLog::firstOrCreate(
            [
                'action' => 'demo.platform_review',
                'subject_label' => 'Summit Hardware',
            ],
            [
                'actor_id' => $admin?->id,
                'tenant_id' => $tenant?->id,
                'description' => 'Reviewed the demo tenant subscription and payment status.',
                'metadata' => ['source' => 'demo_seed'],
                'ip_address' => '127.0.0.1',
            ]
        );
    }
}
