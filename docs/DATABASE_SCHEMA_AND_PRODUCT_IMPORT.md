# Database Schema and Product Import Handoff

This document describes the current Zephrant ERP database structure and the product import model. It is intended for another process or developer that needs to generate products, variants, import files, seeders, or integration code.

## Tenant Model

Most business tables are tenant-scoped using `tenant_id`. Platform tables such as plans, offers, platform settings, and platform activity are shared at system level or optionally linked to a tenant.

Core tenant tables:

| Table | Purpose | Important columns |
| --- | --- | --- |
| `tenants` | Business/workspace record | `id`, `name`, `slug`, `email`, `phone`, `address`, `deleted_at`, timestamps |
| `users` | Platform admins and tenant users | `id`, `tenant_id`, `name`, `email`, `password`, `is_platform_admin`, `is_active`, `requires_password_setup`, `terms_accepted_at`, timestamps |
| `model_has_roles`, `roles`, `permissions` | Spatie role/permission tables | Used for owner/cashier/platform permissions |
| `sessions`, `cache`, `jobs`, `failed_jobs` | Laravel runtime tables | Framework infrastructure |

## Product Catalog Schema

Product data is split into a parent product row and one or more variant rows.

### `categories`

Hierarchical product categories.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `tenant_id` | Tenant owner |
| `parent_id` | Nullable self-reference to `categories.id` |
| `name` | Category name |
| `deleted_at` | Soft delete |
| timestamps | Created/updated |

Category path example:

```text
Groceries > Rice > Basmati Rice
```

This creates three category records:

```text
Groceries                 parent_id = null
Rice                      parent_id = Groceries.id
Basmati Rice              parent_id = Rice.id
```

The product stores the deepest category in `products.category_id`.

### `brands`

Optional product brand/manufacturer.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `tenant_id` | Tenant owner |
| `name` | Brand name, unique per tenant |
| `slug` | Brand slug, unique per tenant |
| `description` | Optional |
| `is_active` | Active/inactive |
| timestamps | Created/updated |

### `units`

Business-friendly product units.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `tenant_id` | Tenant owner |
| `name` | Unit name, unique per tenant, for example `Piece`, `KG`, `Bag` |
| `symbol` | Short symbol, for example `pc`, `kg`, `bag` |
| `description` | Optional helper text |
| `is_active` | Active/inactive |
| timestamps | Created/updated |

### `products`

Parent product record. It stores shared product details and roll-up values from variants.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `tenant_id` | Tenant owner |
| `category_id` | Deepest category |
| `brand_id` | Optional brand |
| `name` | Product name |
| `slug` | Unique per tenant |
| `description` | Optional |
| `sku` | Mirrors the default/first variant SKU |
| `barcode` | Mirrors the default/first variant barcode |
| `purchase_price` | Mirrors default/first variant customer-unit purchase cost |
| `selling_price` | Mirrors default/first variant customer-unit selling price |
| `stock_quantity` | Sum of active variant stock in customer units |
| `low_stock_alert` | Sum of active variant alerts |
| `has_variants` | `true` when active variants count is greater than 1 |
| `is_active` | Product status |
| `is_online_enabled` | Future online listing flag |
| `deleted_at` | Soft delete |
| timestamps | Created/updated |

Important: simple products still have one internal variant row named `Default`. The UI should display that product as a simple product, not as a visible variant.

### `product_variants`

Stock, price, SKU, barcode, and unit data live here. Invoices, purchases, returns, and stock movements should point to the variant whenever possible.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `tenant_id` | Tenant owner |
| `product_id` | Parent product |
| `name` | Variant name. Simple products use internal `Default` |
| `sku` | Required, unique per tenant |
| `barcode` | Optional, unique per tenant when present |
| `unit_id` | Customer/selling unit |
| `purchase_unit_id` | Supplier/buying unit |
| `purchase_unit_factor` | Number of customer units in one supplier unit |
| `purchase_unit_price` | Supplier-unit purchase price |
| `purchase_price` | Customer-unit purchase cost, calculated as supplier price / factor |
| `selling_price` | Customer-unit selling price |
| `compare_at_price` | Optional regular/original price shown as customer savings |
| `stock_quantity` | Stock in customer units |
| `low_stock_alert` | Alert threshold in customer units |
| `track_stock` | Whether stock is tracked |
| `is_default` | First/default variant |
| `is_active` | Variant status |
| `deleted_at` | Soft delete |
| timestamps | Created/updated |

Unit example:

```text
Rice
Customer Unit: KG
Supplier Unit: Bag
purchase_unit_factor: 50
purchase_unit_price: 12000
purchase_price: 240
selling_price: 280
```

If the user enters `Opening Supplier Quantity = 3` and `Additional Customer-Unit Stock = 10`, stock is:

```text
(3 bags * 50 KG) + 10 KG = 160 KG
```

Stored as:

```text
product_variants.stock_quantity = 160
products.stock_quantity = 160
```

### `product_attributes`

Variant attribute names such as `Color`, `Size`, `Packing`, `Flavor`.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `tenant_id` | Tenant owner |
| `name` | Unique per tenant |
| `slug` | Unique per tenant |
| `is_active` | Active/inactive |
| timestamps | Created/updated |

### `product_attribute_values`

Attribute options such as `Black`, `Small`, `500ml`.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `product_attribute_id` | Parent attribute |
| `value` | Option value |
| `slug` | Option slug |
| `sort_order` | Display order |
| timestamps | Created/updated |

Unique rule: one value can exist once per attribute.

### `product_variant_values`

Pivot table connecting variants to their attribute option values.

| Column | Notes |
| --- | --- |
| `product_variant_id` | Variant |
| `product_attribute_value_id` | Attribute option |

Composite primary key: `product_variant_id + product_attribute_value_id`.

### `product_images`

Product and future variant image storage.

| Column | Notes |
| --- | --- |
| `id` | Primary key |
| `tenant_id` | Tenant owner |
| `product_id` | Parent product |
| `product_variant_id` | Optional variant-specific image |
| `path` | Storage path |
| `alt_text` | Optional |
| `sort_order` | Display order |
| `is_primary` | Primary image flag |
| timestamps | Created/updated |

### Online Sales Compatibility

Tables already reserved for future online selling:

| Table | Purpose |
| --- | --- |
| `sales_channels` | Online sales channel definitions |
| `product_channel_listings` | Product listing title, slug, description, SEO, visibility per channel |

## Product Storage Rules

### Simple Product

A simple product has:

```text
products.has_variants = false
one product_variants row
product_variants.name = Default
product_variants.is_default = true
```

Example:

```text
Product: Basmati Rice
Variant: Default
Customer Unit: KG
Supplier Unit: Bag
Factor: 50
Stock: 160 KG
```

The UI should show this as:

```text
Simple product
```

It should not show `Default Variant` to the user.

### Variant Product

A variant product has:

```text
products.has_variants = true
two or more active product_variants rows
each variant has its own SKU, barcode, stock, price, and attributes
```

Example:

```text
Product: Cotton T-Shirt
Variant 1: Black / Small
Variant 2: Blue / Large
```

Stored as:

```text
products.name = Cotton T-Shirt
product_variants.name = Black / Small
product_variant_values = Color: Black, Size: Small
```

## Recommended Product CSV Columns

Use these columns in this order. This matches the current import template and should be used by external generation processes.

The first ready-to-use sample file is available at [docs/examples/default-products.csv](examples/default-products.csv).

| Column | Required | Purpose |
| --- | --- | --- |
| `Product Name` | Yes | Parent product name |
| `Product Group SKU` | No | Same value groups multiple rows into one variant product |
| `Variant Name` | No | Optional display name, for example `Black / Small` |
| `Attribute 1` | No | Attribute name, for example `Color` |
| `Option 1` | No | Attribute value, for example `Black` |
| `Attribute 2` | No | Attribute name, for example `Size` |
| `Option 2` | No | Attribute value, for example `Small` |
| `Attribute 3` | No | Attribute name |
| `Option 3` | No | Attribute value |
| `Parent Category` | No | Top-level category |
| `Category` | Yes | Main/import category. If parent/subcategory also exists, this is middle level |
| `Subcategory` | No | Deepest category under Category |
| `Brand` | No | Brand/manufacturer name |
| `SKU` | Yes | Unique variant SKU |
| `Barcode` | No | Unique variant barcode |
| `Description` | No | Product description |
| `Customer Unit` | Yes | Unit used for selling and stock, for example `KG`, `Piece`, `Liter` |
| `Supplier Unit` | No | Unit used for buying, for example `Bag`, `Carton`. Defaults to Customer Unit if blank |
| `Customer Units in One Supplier Unit` | No | Conversion factor. Defaults to `1` |
| `Purchase Price` | Yes | Price for one Supplier Unit |
| `Selling Price` | Yes | Price for one Customer Unit |
| `Regular Price` | No | Optional original price for savings/compare display |
| `Opening Supplier Quantity` | No | Opening stock in Supplier Unit |
| `Additional Customer-Unit Stock` | No | Extra loose stock in Customer Unit |
| `Low Stock Alert` | No | Alert threshold in Customer Unit |
| `Status` | No | `Active` or `Inactive` |

Required importer fields:

```text
Product Name
Category
SKU
Customer Unit
Purchase Price
Selling Price
```

### Simple Product CSV Example

```csv
Product Name,Product Group SKU,Variant Name,Attribute 1,Option 1,Attribute 2,Option 2,Attribute 3,Option 3,Parent Category,Category,Subcategory,Brand,SKU,Barcode,Description,Customer Unit,Supplier Unit,Customer Units in One Supplier Unit,Purchase Price,Selling Price,Regular Price,Opening Supplier Quantity,Additional Customer-Unit Stock,Low Stock Alert,Status
Basmati Rice,,,,,,,,,Groceries,Rice,Basmati Rice,Store Brand,RICE-BAS-001,896400000001,Premium basmati rice,KG,Bag,50,12000,280,300,3,10,10,Active
```

This creates one product and one internal `Default` variant.

### Variant Product CSV Example

Rows with the same `Product Group SKU` become variants under one product.

```csv
Product Name,Product Group SKU,Variant Name,Attribute 1,Option 1,Attribute 2,Option 2,Attribute 3,Option 3,Parent Category,Category,Subcategory,Brand,SKU,Barcode,Description,Customer Unit,Supplier Unit,Customer Units in One Supplier Unit,Purchase Price,Selling Price,Regular Price,Opening Supplier Quantity,Additional Customer-Unit Stock,Low Stock Alert,Status
Cotton T-Shirt,TSHIRT,Black / Small,Color,Black,Size,Small,,,Clothing,Shirts,,Demo Fashion,TSHIRT-BLK-S,896400000101,Cotton crew-neck shirt,Piece,Carton,24,12000,750,900,1,0,5,Active
Cotton T-Shirt,TSHIRT,Blue / Large,Color,Blue,Size,Large,,,Clothing,Shirts,,Demo Fashion,TSHIRT-BLU-L,896400000102,Cotton crew-neck shirt,Piece,Carton,24,12000,800,950,1,6,5,Active
```

This creates:

```text
1 product: Cotton T-Shirt
2 variants:
- Black / Small, stock 24 Piece
- Blue / Large, stock 30 Piece
```

## Import Validation Rules

- SKU must be unique across `products.sku` and `product_variants.sku`.
- Barcode must be unique across `products.barcode` and `product_variants.barcode` when present.
- Rows with the same Product Group SKU must use the same Product Name.
- Variant SKUs must be unique inside the CSV file.
- Variant barcodes must be unique inside the CSV file.
- Numeric fields must be non-negative.
- Converted opening stock must result in a whole customer-unit quantity.
- If `create_missing` is enabled, categories, brands, units, attributes, and options are created automatically.

## Sales and Customer Accounts

| Table | Purpose | Key columns |
| --- | --- | --- |
| `customers` | Customer master | `tenant_id`, `name`, `phone`, `email`, `address`, `opening_balance`, soft deletes |
| `invoices` | Sales invoice header | `tenant_id`, `customer_id`, `invoice_no`, `sale_date`, `subtotal`, `tax`, `discount`, `total`, `paid_amount`, `remaining_amount`, `status`, `notes`, soft deletes |
| `invoice_items` | Invoice lines | `invoice_id`, `product_id`, `product_variant_id`, `quantity`, `price`, `regular_price`, `item_savings`, `total` |
| `customer_payments` | Customer payments | `tenant_id`, `customer_id`, `invoice_id`, `amount`, `payment_method`, `payment_date`, `reference_no`, `notes`, soft deletes |
| `sales_returns` | Customer return header | `tenant_id`, `invoice_id`, `customer_id`, `return_no`, `return_date`, `total_amount`, `notes` |
| `sales_return_items` | Customer return lines | `sales_return_id`, `invoice_item_id`, `product_id`, `product_variant_id`, `quantity`, `price`, `total` |

Invoice and return rows should point to `product_variant_id` when available. Sales decrease variant stock. Sales returns restore variant stock.

## Purchase and Supplier Accounts

| Table | Purpose | Key columns |
| --- | --- | --- |
| `suppliers` | Supplier master | `tenant_id`, `name`, `phone`, `email`, `address`, `opening_balance`, soft deletes |
| `purchases` | Purchase header | `tenant_id`, `supplier_id`, `purchase_no`, `purchase_date`, `subtotal`, `discount`, `extra_expense`, `total`, `paid_amount`, `remaining_amount`, `status`, `notes`, soft deletes |
| `purchase_items` | Purchase lines | `purchase_id`, `product_id`, `product_variant_id`, `unit_id`, `unit_factor`, `quantity`, `base_quantity`, `purchase_price`, `line_total` |
| `supplier_payments` | Supplier payments | `tenant_id`, `supplier_id`, `purchase_id`, `amount`, `payment_method`, `payment_date`, `reference_no`, `notes`, soft deletes |
| `purchase_returns` | Supplier return header | `tenant_id`, `purchase_id`, `supplier_id`, `return_no`, `return_date`, `total_amount`, `notes` |
| `purchase_return_items` | Supplier return lines | `purchase_return_id`, `purchase_item_id`, `product_id`, `product_variant_id`, `quantity`, `purchase_price`, `total` |

Purchases increase variant stock. Purchase returns decrease variant stock.

## Stock Ledger

| Table | Purpose | Key columns |
| --- | --- | --- |
| `stock_movements` | Immutable stock movement history | `tenant_id`, `product_id`, `product_variant_id`, `type`, `direction`, `quantity`, `unit_cost`, `unit_price`, `stock_after`, `source_type`, `source_id`, `reference_no`, `movement_date`, `notes` |

Common movement types:

```text
opening_stock
stock_adjustment_in
stock_adjustment_out
purchase
purchase_return
sale
sales_return
```

## Expenses

| Table | Purpose | Key columns |
| --- | --- | --- |
| `expenses` | Business expenses | `tenant_id`, `title`, `category`, `amount`, `expense_date`, `notes`, soft deletes |

## Platform Subscription and Billing

| Table | Purpose | Key columns |
| --- | --- | --- |
| `subscription_plans` | Public/admin plans | `name`, `slug`, `description`, `monthly_price_cents`, `annual_price_cents`, `user_limit`, `trial_days`, `free_access_days`, `product_limit`, `monthly_invoice_limit`, `is_public`, `is_active` |
| `plan_features` | Feature catalog | `name`, `key`, `description`, `is_paid` |
| `feature_plan` | Plan-feature pivot | `subscription_plan_id`, `plan_feature_id`, `limits` JSON |
| `tenant_subscriptions` | Tenant subscription state | `tenant_id`, `subscription_plan_id`, `status`, `starts_at`, `trial_ends_at`, `ends_at` |
| `platform_offers` | Discount/trial offers | `name`, `code`, `discount_type`, `discount_value`, `billing_cycle`, `trial_days`, `redemption_limit`, `redeemed_count`, `starts_at`, `ends_at`, `is_active` |
| `offer_subscription_plan` | Offer-plan pivot | `platform_offer_id`, `subscription_plan_id` |
| `platform_subscription_invoices` | Platform billing invoices | `tenant_id`, `tenant_subscription_id`, `platform_offer_id`, `invoice_no`, `billing_period`, `billing_cycle`, `subtotal_cents`, `discount_cents`, `tax_cents`, `total_cents`, `paid_cents`, `balance_cents`, `status`, `issued_on`, `due_on`, `notes` |
| `platform_subscription_payments` | Approved platform payments | `platform_subscription_invoice_id`, `tenant_id`, `amount_cents`, `payment_method`, `reference_no`, `paid_on`, `notes` |
| `subscription_payment_submissions` | Tenant-submitted payment references | `platform_subscription_invoice_id`, `tenant_id`, `submitted_by`, `payment_method`, `reference_no`, `paid_on`, `notes`, `status`, `reviewed_by`, `reviewed_at`, `rejection_reason` |
| `platform_settings` | Platform-level settings | `platform_name`, `support_email`, `support_phone`, `currency_code`, `payment_instructions`, `payment_channels` JSON, `allow_registration` |
| `platform_activity_logs` | Platform audit trail | `actor_id`, `tenant_id`, `action`, `subject_type`, `subject_id`, `subject_label`, `description`, `metadata` JSON, `ip_address` |

## Generation Recommendations

For default product generation:

1. Generate units first: `Piece`, `KG`, `Gram`, `Liter`, `ML`, `Bag`, `Carton`, `Box`, `Tray`, `Dozen`.
2. Generate category tree before products.
3. Generate brands before products when possible.
4. Generate one CSV row per simple product.
5. Generate one CSV row per variant for variant products.
6. Use the same `Product Group SKU` for all variants of the same parent product.
7. Keep `SKU` unique per variant, not only per product.
8. Store stock in customer units after conversion.
9. Use `Regular Price` only when it is higher than `Selling Price`.
10. Prefer business-friendly unit names in imports: `Customer Unit`, `Supplier Unit`, and `Customer Units in One Supplier Unit`.

## Current Import Template Source

The downloadable template is generated by:

```text
app/Http/Controllers/ProductImportController.php
app/Services/ProductImportService.php
```

If CSV columns change, update:

```text
ProductImportService::FIELDS
ProductImportService::REQUIRED_FIELDS
ProductImportService::suggestedMapping()
ProductImportController::template()
tests/Feature/ProductImportTest.php
```
