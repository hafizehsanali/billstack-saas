<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // STORE 1

        $tenant1 = Tenant::create([
            'name' => 'Alpha Store',
             'slug' => 'alpha-store',
        ]);

        $user1 = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Alpha Admin',
            'email' => 'alpha@example.com',
            'password' => Hash::make('password'),
        ]);
        $user1->assignRole('owner');
        // STORE 2
       $tenant2 = Tenant::create([
            'name' => 'Beta Mart',
            'slug' => 'beta-store',
        ]);

        $user2 = User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Beta Admin',
            'email' => 'beta@example.com',
            'password' => Hash::make('password'),
        ]);
        $user2->assignRole('owner');
    }
}
