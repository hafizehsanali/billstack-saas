<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            TenantSeeder::class,
            SaasPlanSeeder::class,
            PlatformOfferSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            ProductSeeder::class,
            InvoiceSeeder::class,
            CustomerPaymentSeeder::class,
            PurchaseSeeder::class,
            ExpenseSeeder::class,
            SupplierPaymentSeeder::class,
            PlatformBillingSeeder::class,
            SubscriptionLifecycleSeeder::class,
        ]);
    }
}
