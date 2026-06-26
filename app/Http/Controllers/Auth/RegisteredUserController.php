<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\BusinessPreset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Models\Tenant;
use App\Services\TenantSubscriptionService;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(Request $request): View
    {
        $settings = PlatformSetting::current();

        if (! $settings->allow_registration) {
            return view('auth.registration-closed', compact('settings'));
        }

        $selectedPlan = SubscriptionPlan::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('slug', $request->string('plan'))
            ->first();
        $selectedCycle = in_array($request->string('cycle')->toString(), ['monthly', 'annual'], true)
            ? $request->string('cycle')->toString()
            : 'monthly';
        $selectedPromo = Str::upper($request->string('promo')->toString());

        $businessPresets = BusinessPreset::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('auth.register', compact('selectedPlan', 'selectedCycle', 'selectedPromo', 'businessPresets'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        abort_unless(PlatformSetting::current()->allow_registration, 403);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_preset_id' => [
                'required',
                'integer',
                Rule::exists('business_presets', 'id')->where('is_active', true),
            ],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'terms' => ['accepted'],
            'plan' => [
                'nullable',
                'string',
                Rule::exists('subscription_plans', 'slug')
                    ->where('is_public', true)
                    ->where('is_active', true),
            ],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'annual'])],
            'promo_code' => ['nullable', 'string', 'max:50', 'alpha_dash'],
        ]);
        $businessPreset = BusinessPreset::findOrFail($request->business_preset_id);
        
        $tenant = Tenant::create([
            'name' => $request->business_name,
            'slug' => Str::slug($request->business_name . '-' . uniqid()),
            'business_preset_id' => $businessPreset->id,
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'terms_accepted_at' => now(),
        ]);

        Role::findOrCreate('owner');
        $user->assignRole('owner');
        $selectedPlan = SubscriptionPlan::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('slug', $request->input('plan'))
            ->first();

        $selectedPlan
            ? $subscriptions->subscribe($tenant, $selectedPlan)
            : $subscriptions->assignDefaultPlan($tenant);

        event(new Registered($user));

        Auth::login($user);

        if (! $tenant->activeSubscription && $selectedPlan?->monthly_price_cents > 0) {
            return redirect()->route('subscription.checkout', array_filter([
                'billing_cycle' => $request->input('billing_cycle', 'monthly'),
                'promo_code' => Str::upper($request->input('promo_code', '')),
            ]));
        }

        return redirect(route(
            $tenant->activeSubscription ? 'dashboard' : 'subscription.status',
            absolute: false
        ));
    }
}
