<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessSettingsRequest;
use App\Services\TenantModuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function business(TenantModuleService $modules): View
    {
        $tenant = auth()->user()->tenant;

        return view('settings.business', [
            'tenant' => $tenant,
            'enabledModules' => $modules->enabledModules($tenant),
        ]);
    }

    public function updateBusiness(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        auth()->user()->tenant->update($request->validated());

        return back()->with('success', 'Business settings updated successfully.');
    }
}
