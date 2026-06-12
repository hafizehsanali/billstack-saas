<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UpdatePlatformSettingRequest;
use App\Models\PlatformSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Services\PlatformActivityService;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('platform.settings.edit', [
            'settings' => PlatformSetting::current(),
        ]);
    }

    public function update(
        UpdatePlatformSettingRequest $request,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $data = $request->validated();
        $data['currency_code'] = strtoupper($data['currency_code']);
        $data['allow_registration'] = (bool) ($data['allow_registration'] ?? false);

        $settings = PlatformSetting::current();
        $settings->update($data);
        $activity->record(
            'settings.updated',
            'Updated platform identity, support, payment, or registration settings.',
            $settings
        );

        return back()->with('success', 'Platform settings updated successfully.');
    }
}
