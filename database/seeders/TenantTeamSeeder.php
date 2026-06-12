<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantTeamSeeder extends Seeder
{
    /**
     * Seed staff accounts for testing tenant roles and account status controls.
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-store-1')->first();

        if (! $tenant) {
            return;
        }

        $members = [
            [
                'name' => 'Demo Cashier',
                'email' => 'cashier@test.com',
                'role' => 'cashier',
                'is_active' => true,
            ],
            [
                'name' => 'Demo Manager',
                'email' => 'manager@test.com',
                'role' => 'manager',
                'is_active' => false,
            ],
            [
                'name' => 'Demo Accountant',
                'email' => 'accountant@test.com',
                'role' => 'accountant',
                'is_active' => false,
            ],
            [
                'name' => 'Demo Inventory Staff',
                'email' => 'inventory@test.com',
                'role' => 'inventory_staff',
                'is_active' => false,
            ],
        ];

        foreach ($members as $memberData) {
            $member = User::updateOrCreate(
                ['email' => $memberData['email']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $memberData['name'],
                    'password' => Hash::make('password'),
                    'is_active' => $memberData['is_active'],
                ]
            );

            $member->forceFill(['email_verified_at' => now()])->save();
            $member->syncRoles([$memberData['role']]);
        }
    }
}
