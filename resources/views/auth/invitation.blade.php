<x-guest-layout>
    <div class="mb-4 text-center">
        <h2 class="text-xl font-bold text-slate-800">Welcome to CSN Alert System</h2>
        <p class="mt-1 text-xs text-slate-500">Set your password to activate your account for <span class="font-semibold text-slate-700">{{ $user->email }}</span>.</p>
        <div class="mt-2 inline-flex items-center gap-1 rounded-md bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-700">
            <span>Role:</span>
            <span class="capitalize">{{ $user->roles->first()?->name ?? $user->role ?? 'Staff' }}</span>
        </div>
    </div>

    <form method="POST" action="{{ request()->fullUrl() }}">
        @csrf

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" autofocus />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full justify-center min-h-[44px]">
                {{ __('Activate Account & Sign In') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

