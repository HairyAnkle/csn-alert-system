<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">Sign in to your account</h2>
        <p class="text-xs text-slate-500 mt-1">Authorized personnel only &bull; CSN Command Center</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5" />
            <x-text-input id="email" class="block w-full" type="email" name="email" :value="old('email')" placeholder="name@csn.gov.ph" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <x-input-label for="password" :value="__('Password')" class="text-xs font-bold uppercase tracking-wider text-slate-600" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-brand-700 hover:text-brand-800 hover:underline focus:outline-none" href="{{ route('password.request') }}">
                        {{ __('Forgot password?') }}
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-red-600" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer select-none">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-700" name="remember">
                <span class="ms-2 text-xs font-medium text-slate-600">{{ __('Remember this device') }}</span>
            </label>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-2.5 text-sm font-semibold tracking-wide">
                {{ __('Sign In') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
