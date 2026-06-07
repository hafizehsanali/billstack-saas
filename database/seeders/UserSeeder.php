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
        $tenants = Tenant::query()
            ->whereIn('slug', ['demo-store-1', 'demo-store-2', 'demo-store-3'])
            ->get()
            ->keyBy('slug');

        $user1 = User::create([
            'tenant_id' => $tenants->get('demo-store-1')->id,
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password'),
        ]);
        $user1->assignRole('owner');

        $user2 = User::create([
            'tenant_id' => $tenants->get('demo-store-2')->id,
            'name' => 'Alpha Admin',
            'email' => 'alpha@example.com',
            'password' => Hash::make('password'),
        ]);
        $user2->assignRole('owner');

        $user3 = User::create([
            'tenant_id' => $tenants->get('demo-store-3')->id,
            'name' => 'Beta Admin',
            'email' => 'beta@example.com',
            'password' => Hash::make('password'),
        ]);
        $user3->assignRole('owner');
    }
}
