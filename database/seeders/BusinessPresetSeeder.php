<?php

namespace Database\Seeders;

use App\Models\BusinessModule;
use App\Models\BusinessPreset;
use Illuminate\Database\Seeder;

class BusinessPresetSeeder extends Seeder
{
    public function run(): void
    {
        $modules = collect($this->modules())->mapWithKeys(function (array $module, int $index): array {
            $model = BusinessModule::updateOrCreate(
                ['key' => $module['key']],
                [
                    'name' => $module['name'],
                    'description' => $module['description'],
                    'category' => $module['category'],
                    'is_core' => $module['is_core'] ?? false,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );

            return [$model->key => $model];
        });

        foreach ($this->presets() as $index => $preset) {
            $model = BusinessPreset::updateOrCreate(
                ['slug' => $preset['slug']],
                [
                    'name' => $preset['name'],
                    'description' => $preset['description'],
                    'icon' => $preset['icon'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );

            $model->modules()->sync(
                collect($preset['modules'])
                    ->map(fn (string $key) => $modules[$key]?->id)
                    ->filter()
                    ->values()
                    ->all()
            );
        }
    }

    private function modules(): array
    {
        return [
            ['key' => BusinessModule::INVENTORY, 'name' => 'Inventory', 'category' => 'core', 'is_core' => true, 'description' => 'Products, stock balances, categories, brands, and inventory lists.'],
            ['key' => BusinessModule::BILLING, 'name' => 'Billing and POS', 'category' => 'core', 'is_core' => true, 'description' => 'Invoices, POS checkout, receipts, and customer payments.'],
            ['key' => BusinessModule::PURCHASES, 'name' => 'Purchases', 'category' => 'core', 'is_core' => true, 'description' => 'Supplier purchases, purchase payments, and purchase history.'],
            ['key' => BusinessModule::RETURNS, 'name' => 'Returns', 'category' => 'core', 'is_core' => true, 'description' => 'Customer returns, supplier returns, and related stock corrections.'],
            ['key' => BusinessModule::CUSTOMER_LEDGER, 'name' => 'Customer Ledger', 'category' => 'accounts', 'description' => 'Customer balances, statements, receivables, and payment history.'],
            ['key' => BusinessModule::SUPPLIER_LEDGER, 'name' => 'Supplier Ledger', 'category' => 'accounts', 'description' => 'Supplier balances, statements, payables, and payment history.'],
            ['key' => BusinessModule::REPORTS, 'name' => 'Reports', 'category' => 'insights', 'description' => 'Sales, purchases, profit, stock, and account reports.'],
            ['key' => BusinessModule::LOW_STOCK_ALERTS, 'name' => 'Low Stock Alerts', 'category' => 'alerts', 'description' => 'Alerts for products that need restocking.'],
            ['key' => BusinessModule::PRODUCT_VARIANTS, 'name' => 'Product Variants', 'category' => 'inventory', 'description' => 'Pack sizes, flavors, colors, and other product options.'],
            ['key' => BusinessModule::UNIT_CONVERSIONS, 'name' => 'Unit Conversions', 'category' => 'inventory', 'description' => 'Buy in supplier units and sell in customer units.'],
            ['key' => BusinessModule::BULK_PRICING, 'name' => 'Wholesale Pricing', 'category' => 'sales', 'description' => 'Price tiers for wholesale and bulk customers.'],
            ['key' => BusinessModule::BATCH_EXPIRY, 'name' => 'Batch and Expiry Tracking', 'category' => 'inventory', 'description' => 'Batch numbers, manufacturing dates, expiry dates, and expiry alerts.'],
            ['key' => BusinessModule::SERIAL_WARRANTY, 'name' => 'Serial and Warranty Tracking', 'category' => 'inventory', 'description' => 'Unique serial numbers, IMEI style tracking, and warranty-ready sales.'],
            ['key' => BusinessModule::SERVICES, 'name' => 'Service Items', 'category' => 'sales', 'description' => 'Non-stock charges such as delivery, repair, tailoring, and installation.'],
            ['key' => BusinessModule::ONLINE_STORE, 'name' => 'Online Store', 'category' => 'commerce', 'description' => 'Future online catalog, availability, and channel listings.'],
            ['key' => BusinessModule::TEAM_MANAGEMENT, 'name' => 'Team Management', 'category' => 'access', 'is_core' => true, 'description' => 'Owner-controlled staff users, roles, invitations, and active account access.'],
        ];
    }

    private function presets(): array
    {
        $core = [
            BusinessModule::INVENTORY,
            BusinessModule::BILLING,
            BusinessModule::PURCHASES,
            BusinessModule::RETURNS,
            BusinessModule::CUSTOMER_LEDGER,
            BusinessModule::SUPPLIER_LEDGER,
            BusinessModule::REPORTS,
            BusinessModule::LOW_STOCK_ALERTS,
            BusinessModule::PRODUCT_VARIANTS,
            BusinessModule::UNIT_CONVERSIONS,
            BusinessModule::SERVICES,
            BusinessModule::TEAM_MANAGEMENT,
        ];

        return [
            [
                'name' => 'General Store',
                'slug' => BusinessPreset::GENERAL_STORE,
                'icon' => 'store',
                'description' => 'Simple inventory, billing, purchases, returns, and customer/supplier balances for daily retail.',
                'modules' => $core,
            ],
            [
                'name' => 'Pharmacy / Medical Store',
                'slug' => BusinessPreset::PHARMACY,
                'icon' => 'cross',
                'description' => 'Retail pharmacy workflow with batch numbers, expiry alerts, and medicine stock control.',
                'modules' => [...$core, BusinessModule::BATCH_EXPIRY],
            ],
            [
                'name' => 'Hardware Store',
                'slug' => BusinessPreset::HARDWARE,
                'icon' => 'wrench',
                'description' => 'Stock, units, suppliers, customer dues, and mixed retail/wholesale selling for hardware shops.',
                'modules' => [...$core, BusinessModule::BULK_PRICING],
            ],
            [
                'name' => 'Electronics Store',
                'slug' => BusinessPreset::ELECTRONICS,
                'icon' => 'smartphone',
                'description' => 'Electronics inventory with serial-number-ready tracking and service item support.',
                'modules' => [...$core, BusinessModule::SERIAL_WARRANTY],
            ],
            [
                'name' => 'Wholesale / Distributor',
                'slug' => BusinessPreset::WHOLESALE,
                'icon' => 'warehouse',
                'description' => 'Wholesale pricing, supplier units, customer ledgers, and stock movement control.',
                'modules' => [...$core, BusinessModule::BULK_PRICING, BusinessModule::ONLINE_STORE],
            ],
            [
                'name' => 'Services Business',
                'slug' => BusinessPreset::SERVICES,
                'icon' => 'briefcase-business',
                'description' => 'Invoices, customer balances, reports, and non-stock services with optional inventory.',
                'modules' => [
                    BusinessModule::BILLING,
                    BusinessModule::CUSTOMER_LEDGER,
                    BusinessModule::REPORTS,
                    BusinessModule::SERVICES,
                    BusinessModule::TEAM_MANAGEMENT,
                ],
            ],
        ];
    }
}
