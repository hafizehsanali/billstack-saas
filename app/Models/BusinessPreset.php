<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessPreset extends Model
{
    public const GENERAL_STORE = 'general-store';
    public const PHARMACY = 'pharmacy';
    public const HARDWARE = 'hardware';
    public const ELECTRONICS = 'electronics';
    public const WHOLESALE = 'wholesale';
    public const SERVICES = 'services';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(BusinessModule::class, 'business_module_business_preset')
            ->withTimestamps();
    }

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }
}
