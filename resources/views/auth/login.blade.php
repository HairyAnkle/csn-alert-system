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

        <!-- Remember Me & Terms Agreement (Table 1.13) -->
        <div class="space-y-2.5 pt-1">
            <label for="terms_agreed" class="flex items-center cursor-pointer select-none">
                <input id="terms_agreed" type="checkbox" required style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" class="shrink-0 rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-700" name="terms_agreed" {{ old('terms_agreed', true) ? 'checked' : '' }}>
                <span class="ms-2.5 text-xs text-slate-600 leading-snug">
                    I agree to the <span class="text-brand-700 font-semibold hover:underline">Terms and Conditions</span> and Data Privacy Act (RA 10173).
                </span>
            </label>

            <label for="remember_me" class="flex items-center cursor-pointer select-none">
                <input id="remember_me" type="checkbox" style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" class="shrink-0 rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-700" name="remember">
                <span class="ms-2.5 text-xs font-medium text-slate-600">{{ __('Remember this device') }}</span>
            </label>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-2.5 text-sm font-semibold tracking-wide">
                {{ __('Login') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
