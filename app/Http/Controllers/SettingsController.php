<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateBusinessSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function business(): View
    {
        return view('settings.business', [
            'tenant' => auth()->user()->tenant,
        ]);
    }

    public function updateBusiness(UpdateBusinessSettingsRequest $request): RedirectResponse
    {
        auth()->user()->tenant->update($request->validated());

        return back()->with('success', 'Business settings updated successfully.');
    }
}
