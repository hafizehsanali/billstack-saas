<x-guest-layout>
    <div class="mb-5">
        <h1 class="text-xl font-semibold text-gray-900">Create your BillStack workspace</h1>
        <p class="mt-1 text-sm text-gray-600">
            Set up your business account for inventory, billing, customers, and suppliers.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        @if($selectedPlan)
            <input type="hidden" name="plan" value="{{ $selectedPlan->slug }}">
            <div class="mb-4 rounded-md border border-indigo-200 bg-indigo-50 px-4 py-3">
                <div class="text-xs font-semibold uppercase text-indigo-700">Selected package</div>
                <div class="mt-1 font-semibold text-gray-900">{{ $selectedPlan->name }}</div>
                <div class="text-sm text-gray-600">
                    {{ $selectedPlan->monthly_price_cents === 0
                        ? 'Free'
                        : 'Rs '.number_format($selectedPlan->monthly_price_cents / 100, 2).' per month' }}
                </div>
            </div>
        @endif

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <!-- Business Name -->
        <div class="mt-4">
            <x-input-label for="business_name"
                        :value="__('Business Name')" />

            <x-text-input id="business_name"
                        class="block mt-1 w-full"
                        type="text"
                        name="business_name"
                        :value="old('business_name')"
                        required />

            <x-input-error :messages="$errors->get('business_name')"
                        class="mt-2" />
        </div>
        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="me-auto underline text-sm text-gray-600 hover:text-gray-900" href="{{ route('plans.index') }}">
                View packages
            </a>
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Create Account') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
