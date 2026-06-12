<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $platformAdmin = User::updateOrCreate(
            ['email' => 'platform@test.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'is_platform_admin' => true,
            ]
        );
        $platformAdmin->forceFill(['email_verified_at' => now()])->save();

        $tenants = Tenant::query()
            ->whereIn('slug', ['demo-store-1', 'demo-store-2', 'demo-store-3'])
            ->get()
            ->keyBy('slug');

        $user1 = User::updateOrCreate(
            ['email' => 'owner@test.com'],
            [
                'tenant_id' => $tenants->get('demo-store-1')->id,
                'name' => 'Owner',
                'password' => Hash::make('password'),
            ]
        );
        $user1->forceFill(['email_verified_at' => now()])->save();
        $user1->assignRole('owner');

        $user2 = User::updateOrCreate(
            ['email' => 'alpha@example.com'],
            [
                'tenant_id' => $tenants->get('demo-store-2')->id,
                'name' => 'Alpha Admin',
                'password' => Hash::make('password'),
            ]
        );
        $user2->forceFill(['email_verified_at' => now()])->save();
        $user2->assignRole('owner');

        $user3 = User::updateOrCreate(
            ['email' => 'beta@example.com'],
            [
                'tenant_id' => $tenants->get('demo-store-3')->id,
                'name' => 'Beta Admin',
                'password' => Hash::make('password'),
            ]
        );
        $user3->forceFill(['email_verified_at' => now()])->save();
        $user3->assignRole('owner');
    }
}
