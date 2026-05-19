<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Auto assign tenant_id on create
        static::creating(function ($model) {

            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });

        // Automatically filter queries by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {

            if (auth()->check()) {
                $builder->where(
                    $builder->getModel()->getTable() . '.tenant_id',
                    auth()->user()->tenant_id
                );
            }
        });
    }
}