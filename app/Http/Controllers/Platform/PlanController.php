<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StorePlanRequest;
use App\Http\Requests\Platform\UpdatePlanRequest;
use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\PlatformActivityService;

class PlanController extends Controller
{
    public function index(): View
    {
        return view('platform.plans.index', [
            'plans' => SubscriptionPlan::withCount(['features', 'subscriptions'])
                ->orderBy('monthly_price_cents')
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('platform.plans.create', [
            'features' => PlanFeature::orderBy('name')->get(),
            'selectedFeatures' => [],
        ]);
    }

    public function store(
        StorePlanRequest $request,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $data = $this->validatedPlanData($request->validated());
        $plan = SubscriptionPlan::create($data);

        $plan->features()->sync($request->validated('features', []));
        $activity->record(
            'plan.created',
            "Created subscription plan {$plan->name}.",
            $plan
        );

        return redirect()
            ->route('platform.plans.index')
            ->with('success', 'Plan created successfully.');
    }

    public function edit(SubscriptionPlan $plan): View
    {
        return view('platform.plans.edit', [
            'plan' => $plan->load('features'),
            'features' => PlanFeature::orderBy('name')->get(),
            'selectedFeatures' => $plan->features->pluck('id')->all(),
        ]);
    }

    public function update(
        UpdatePlanRequest $request,
        SubscriptionPlan $plan,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $plan->update($this->validatedPlanData($request->validated(), $plan));
        $plan->features()->sync($request->validated('features', []));
        $activity->record(
            'plan.updated',
            "Updated subscription plan {$plan->name}.",
            $plan
        );

        return redirect()
            ->route('platform.plans.index')
            ->with('success', 'Plan updated successfully.');
    }

    private function validatedPlanData(array $data, ?SubscriptionPlan $plan = null): array
    {
        $slug = $data['slug'] ?: Str::slug($data['name']);
        $monthlyPriceCents = (int) round(((float) $data['monthly_price']) * 100);

        return [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'monthly_price_cents' => $monthlyPriceCents,
            'annual_price_cents' => (int) round(((float) $data['annual_price']) * 100),
            'user_limit' => $data['user_limit'] ?? null,
            'trial_days' => $monthlyPriceCents > 0 ? $data['trial_days'] : 0,
            'free_access_days' => $monthlyPriceCents === 0
                ? ($data['free_access_days'] ?? null)
                : null,
            'product_limit' => $data['product_limit'] ?? null,
            'monthly_invoice_limit' => $data['monthly_invoice_limit'] ?? null,
            'is_public' => (bool) ($data['is_public'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
