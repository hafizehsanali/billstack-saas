<?php

namespace Database\Seeders;

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
            'customers.edit',
            'customers.delete',

            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',

            'purchases.view',
            'purchases.create',
            'purchases.edit',
            'purchases.cancel',

            'sales.view',
            'sales.create',
            'sales.cancel',

            'payments.view',
            'payments.create',

            'expenses.view',
            'expenses.create',
            'expenses.edit',
            'expenses.delete',

            'reports.view',
            'settings.manage',
            'team.manage',
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

        Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web'])
            ->syncPermissions(array_diff($permissions, ['settings.manage', 'team.manage']));

        Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web'])
            ->syncPermissions([
                'dashboard.view',
                'customers.view',
                'suppliers.view',
                'purchases.view',
                'payments.view',
                'payments.create',
                'expenses.view',
                'expenses.create',
                'expenses.edit',
                'reports.view',
            ]);

        Role::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web'])
            ->syncPermissions([
                'dashboard.view',
                'products.view',
                'customers.view',
                'customers.create',
                'sales.view',
                'sales.create',
                'payments.create',
            ]);

        Role::firstOrCreate(['name' => 'inventory_staff', 'guard_name' => 'web'])
            ->syncPermissions([
                'dashboard.view',
                'products.view',
                'products.create',
                'products.edit',
                'suppliers.view',
                'purchases.view',
                'purchases.create',
            ]);
    }
}
