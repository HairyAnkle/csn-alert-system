<x-guest-layout>
    <div class="text-center space-y-4">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-4 ring-emerald-100">
            <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <div>
            <h2 class="text-xl font-bold text-slate-800">Mobile Account Activated!</h2>
            <p class="mt-1.5 text-sm text-slate-600">
                Your password has been saved for
                <span class="font-semibold text-slate-800">{{ $email ?: 'your account' }}</span>.
            </p>
        </div>

        <div class="rounded-xl border border-sky-200 bg-sky-50 p-4 text-left text-xs text-sky-900 space-y-2">
            <p class="font-bold uppercase tracking-wider text-sky-800">Next Step: Sign In on the Mobile App</p>
            <p>
                As a <strong>{{ $role === 'teacher' ? 'CSN Teacher' : 'Parent / Guardian' }}</strong>, your account uses the
                <strong>Parañaque CSN Mobile App</strong> rather than the Web Command Center.
            </p>
            <ol class="list-decimal list-inside space-y-1 text-sky-800">
                <li>Open the <strong>Parañaque CSN Mobile App</strong> on your phone.</li>
                <li>Tap <strong>Login</strong> and select <strong>{{ $role === 'teacher' ? 'CSN Teacher' : 'Parent / Guardian' }}</strong>.</li>
                <li>Enter your email and the password you just created.</li>
            </ol>
        </div>

        <p class="text-[11px] text-slate-400">
            You may safely close this browser window now.
        </p>
    </div>
</x-guest-layout>
