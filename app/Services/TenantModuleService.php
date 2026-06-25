<?php

namespace App\Services;

use App\Models\BusinessModule;
use App\Models\Tenant;
use Illuminate\Support\Collection;

class TenantModuleService
{
    public function hasModule(?Tenant $tenant, string $moduleKey): bool
    {
        if (! $tenant) {
            return false;
        }

        return $this->enabledModules($tenant)->contains('key', $moduleKey);
    }

    public function enabledModuleKeys(?Tenant $tenant): array
    {
        return $this->enabledModules($tenant)
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * Tenant overrides win over preset defaults, so platform admins can tailor one account
     * without creating a new preset for every customer.
     */
    public function enabledModules(?Tenant $tenant): Collection
    {
        if (! $tenant) {
            return collect();
        }

        $tenant->loadMissing([
            'businessPreset.modules',
            'businessModuleOverrides.module',
        ]);

        $modules = $tenant->businessPreset?->modules
            ? $tenant->businessPreset->modules->where('is_active', true)->keyBy('id')
            : collect();

        foreach ($tenant->businessModuleOverrides as $override) {
            if (! $override->module?->is_active) {
                continue;
            }

            if ($override->is_enabled) {
                $modules->put($override->module->id, $override->module);
            } else {
                $modules->forget($override->module->id);
            }
        }

        return $modules
            ->sortBy([
                ['sort_order', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    public function productModeOptions(?Tenant $tenant): array
    {
        $modes = [
            'loose' => 'Loose item',
            'packed' => 'Packed item',
            'service' => 'Service',
        ];

        if ($this->hasModule($tenant, BusinessModule::PRODUCT_VARIANTS)) {
            $modes['hybrid'] = 'Loose + packed item';
        }

        if ($this->hasModule($tenant, BusinessModule::SERIAL_WARRANTY)) {
            $modes['serialized'] = 'Serialized item';
        }

        if ($this->hasModule($tenant, BusinessModule::BATCH_EXPIRY)) {
            $modes['batch_tracked'] = 'Batch/expiry item';
        }

        return $modes;
    }
}
