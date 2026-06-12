<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreFeatureRequest;
use App\Http\Requests\Platform\UpdateFeatureRequest;
use App\Models\PlanFeature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\PlatformActivityService;

class FeatureController extends Controller
{
    public function index(): View
    {
        return view('platform.features.index', [
            'features' => PlanFeature::withCount('plans')
                ->orderBy('is_paid')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('platform.features.create');
    }

    public function store(
        StoreFeatureRequest $request,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $data = $request->validated();

        $feature = PlanFeature::create([
            'name' => $data['name'],
            'key' => $data['key'] ?: Str::slug($data['name'], '.'),
            'description' => $data['description'] ?? null,
            'is_paid' => (bool) ($data['is_paid'] ?? false),
        ]);
        $activity->record(
            'feature.created',
            "Created plan feature {$feature->name}.",
            $feature
        );

        return redirect()
            ->route('platform.features.index')
            ->with('success', 'Feature created successfully.');
    }

    public function edit(PlanFeature $feature): View
    {
        return view('platform.features.edit', compact('feature'));
    }

    public function update(
        UpdateFeatureRequest $request,
        PlanFeature $feature,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $data = $request->validated();

        $feature->update([
            'name' => $data['name'],
            'key' => $data['key'] ?: Str::slug($data['name'], '.'),
            'description' => $data['description'] ?? null,
            'is_paid' => (bool) ($data['is_paid'] ?? false),
        ]);
        $activity->record(
            'feature.updated',
            "Updated plan feature {$feature->name}.",
            $feature
        );

        return redirect()
            ->route('platform.features.index')
            ->with('success', 'Feature updated successfully.');
    }
}
