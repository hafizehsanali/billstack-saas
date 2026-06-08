<?php

namespace App\Http\Controllers;

use App\Models\PlanFeature;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureUnavailableController extends Controller
{
    public function show(Request $request): View
    {
        $featureKey = (string) $request->query('feature');
        $feature = PlanFeature::where('key', $featureKey)->first();

        return view('features.unavailable', [
            'feature' => $feature,
            'featureKey' => $featureKey,
            'tenant' => $request->user()?->tenant?->load('activeSubscription.plan'),
        ]);
    }
}
