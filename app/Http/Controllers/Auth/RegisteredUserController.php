<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SubscriptionPlan;
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
        $selectedPlan = SubscriptionPlan::query()
            ->where('is_public', true)
            ->where('is_active', true)
            ->where('slug', $request->string('plan'))
            ->first();

        return view('auth.register', compact('selectedPlan'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, TenantSubscriptionService $subscriptions): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'plan' => [
                'nullable',
                'string',
                Rule::exists('subscription_plans', 'slug')
                    ->where('is_public', true)
                    ->where('is_active', true),
            ],
        ]);
        $tenant = Tenant::create([
            'name' => $request->business_name,
            'slug' => Str::slug($request->business_name . '-' . uniqid()),
        ]);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
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

        return redirect(route(
            $tenant->activeSubscription ? 'dashboard' : 'subscription.status',
            absolute: false
        ));
    }
}
