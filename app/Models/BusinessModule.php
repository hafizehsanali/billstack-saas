<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessModule extends Model
{
    public const INVENTORY = 'inventory';
    public const BILLING = 'billing';
    public const PURCHASES = 'purchases';
    public const RETURNS = 'returns';
    public const CUSTOMER_LEDGER = 'customer_ledger';
    public const SUPPLIER_LEDGER = 'supplier_ledger';
    public const REPORTS = 'reports';
    public const LOW_STOCK_ALERTS = 'low_stock_alerts';
    public const PRODUCT_VARIANTS = 'product_variants';
    public const UNIT_CONVERSIONS = 'unit_conversions';
    public const BULK_PRICING = 'bulk_pricing';
    public const BATCH_EXPIRY = 'batch_expiry';
    public const SERIAL_WARRANTY = 'serial_warranty';
    public const SERVICES = 'services';
    public const ONLINE_STORE = 'online_store';
    public const TEAM_MANAGEMENT = 'team_management';

    protected $fillable = [
        'name',
        'key',
        'description',
        'category',
        'is_core',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function presets(): BelongsToMany
    {
        return $this->belongsToMany(BusinessPreset::class, 'business_module_business_preset')
            ->withTimestamps();
    }

    public function tenantOverrides(): HasMany
    {
        return $this->hasMany(TenantBusinessModule::class);
    }
}
