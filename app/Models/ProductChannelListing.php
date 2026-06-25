<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductChannelListing extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'sales_channel_id',
        'product_id',
        'title',
        'slug',
        'description',
        'seo_title',
        'seo_description',
        'is_visible',
    ];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
