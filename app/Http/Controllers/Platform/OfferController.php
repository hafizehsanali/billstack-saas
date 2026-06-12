<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreOfferRequest;
use App\Http\Requests\Platform\UpdateOfferRequest;
use App\Models\PlatformOffer;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use App\Services\PlatformActivityService;

class OfferController extends Controller
{
    public function index(): View
    {
        return view('platform.offers.index', [
            'offers' => PlatformOffer::withCount('plans')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('platform.offers.create', [
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('name')->get(),
            'selectedPlans' => [],
        ]);
    }

    public function store(
        StoreOfferRequest $request,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $offer = PlatformOffer::create($this->offerData($request->validated()));
        $offer->plans()->sync($request->validated('plans', []));
        $activity->record(
            'offer.created',
            "Created promotion {$offer->code}.",
            $offer
        );

        return redirect()
            ->route('platform.offers.index')
            ->with('success', 'Offer created successfully.');
    }

    public function edit(PlatformOffer $offer): View
    {
        return view('platform.offers.edit', [
            'offer' => $offer->load('plans'),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('name')->get(),
            'selectedPlans' => $offer->plans->pluck('id')->all(),
        ]);
    }

    public function update(
        UpdateOfferRequest $request,
        PlatformOffer $offer,
        PlatformActivityService $activity
    ): RedirectResponse
    {
        $offer->update($this->offerData($request->validated()));
        $offer->plans()->sync($request->validated('plans', []));
        $activity->record(
            'offer.updated',
            "Updated promotion {$offer->code}.",
            $offer
        );

        return redirect()
            ->route('platform.offers.index')
            ->with('success', 'Offer updated successfully.');
    }

    private function offerData(array $data): array
    {
        $discountValue = $data['discount_type'] === 'fixed'
            ? (int) round(((float) $data['discount_value']) * 100)
            : (int) $data['discount_value'];

        return [
            'name' => $data['name'],
            'code' => Str::upper($data['code']),
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $discountValue,
            'billing_cycle' => $data['billing_cycle'],
            'trial_days' => 0,
            'redemption_limit' => $data['redemption_limit'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }
}
