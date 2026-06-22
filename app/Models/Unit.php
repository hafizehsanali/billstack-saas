<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'symbol',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function stockVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'unit_id');
    }

    public function purchaseVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class, 'purchase_unit_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function isInUse(): bool
    {
        return $this->stockVariants()->exists()
            || $this->purchaseVariants()->exists()
            || $this->purchaseItems()->exists();
    }
}
