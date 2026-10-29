<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">Register Admin Account</h2>
        <p class="text-xs text-slate-500 mt-1">CSN Command &amp; Alert System &bull; Table 1.12 Specification</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <!-- Full Name -->
        <div>
            <x-input-label for="name" :value="__('Full Name')" class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" />
            <x-text-input id="name" class="block w-full text-sm" type="text" name="name" :value="old('name')" placeholder="Dr. Maria Santos" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1 text-xs text-red-600" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" />
            <x-text-input id="email" class="block w-full text-sm" type="email" name="email" :value="old('email')" placeholder="name@csn.gov.ph" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-red-600" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" />
            <x-text-input id="password" class="block w-full text-sm"
                            type="password"
                            name="password"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1 text-xs text-red-600" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1" />
            <x-text-input id="password_confirmation" class="block w-full text-sm"
                            type="password"
                            name="password_confirmation"
                            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-xs text-red-600" />
        </div>

        <!-- Required Terms and Conditions Checkbox (Table 1.12) -->
        <div class="pt-1">
            <label for="terms_agreed" class="flex items-center cursor-pointer select-none">
                <input id="terms_agreed" type="checkbox" required style="width: 18px; height: 18px; min-width: 18px; min-height: 18px;" class="shrink-0 rounded border-slate-300 text-brand-700 shadow-sm focus:ring-brand-700" name="terms_agreed" {{ old('terms_agreed', true) ? 'checked' : '' }}>
                <span class="ms-2.5 text-xs text-slate-600 leading-snug">
                    I agree to the <span class="text-brand-700 font-semibold hover:underline">Terms and Conditions</span> and Philippine Data Privacy Act (RA 10173).
                </span>
            </label>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-2.5 text-sm font-semibold tracking-wide">
                {{ __('Register Account') }}
            </x-primary-button>
        </div>

        <div class="text-center pt-2">
            <a class="text-xs text-slate-600 hover:text-slate-900 underline" href="{{ route('login') }}">
                {{ __('Already have an account? Login') }}
            </a>
        </div>
    </form>
</x-guest-layout>
