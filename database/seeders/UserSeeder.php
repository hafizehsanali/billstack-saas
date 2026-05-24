<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::create([
            'tenant_id' => 1,
            'name' => 'Owner',
            'email' => 'owner@test.com',
            'password' => Hash::make('password'),
        ]);
        $user1->assignRole('owner');

        $user2 = User::create([
            'tenant_id' => 2,
            'name' => 'Alpha Admin',
            'email' => 'alpha@example.com',
            'password' => Hash::make('password'),
        ]);
        $user2->assignRole('owner');

        $user3 = User::create([
            'tenant_id' => 3,
            'name' => 'Beta Admin',
            'email' => 'beta@example.com',
            'password' => Hash::make('password'),
        ]);
        $user3->assignRole('owner');
    }
}
