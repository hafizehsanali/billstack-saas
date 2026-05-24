<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]
            ->forgetCachedPermissions();

        $permissions = [

            'dashboard.view',

            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            'customers.view',
            'customers.create',

            'suppliers.view',
            'suppliers.create',

            'purchases.view',
            'purchases.create',

            'sales.view',
            'sales.create',
        ];

        foreach ($permissions as $permission) {

            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $ownerRole = Role::firstOrCreate([
            'name' => 'owner',
            'guard_name' => 'web',
        ]);

        $ownerRole->syncPermissions($permissions);
    }
}
