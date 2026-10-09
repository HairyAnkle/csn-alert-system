{{-- ============ SIDEBAR ============ --}}
<aside
    class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col bg-slate-800 text-slate-100 transition-transform duration-200 lg:static lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    {{-- Branding --}}
    <div class="flex items-center gap-3 px-5 py-5 border-b border-slate-700/60">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-700 ring-2 ring-brand-100/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z"/>
                <path d="M12 8v5"/><path d="M12 16h.01"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold leading-tight text-white">Parañaque CSN</p>
            <p class="truncate text-xs text-slate-400">Emergency Alert System</p>
        </div>
        <button class="ml-auto rounded-md p-1.5 text-slate-400 hover:bg-slate-700 lg:hidden focus-ring" @click="sidebarOpen = false" aria-label="Close menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Live connection badge --}}
    <div class="mx-4 mt-4 flex items-center gap-2 rounded-lg bg-slate-700/50 px-3 py-2.5">
        <span class="relative flex h-2.5 w-2.5">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
        </span>
        <span class="text-xs font-medium text-slate-200">Reverb Live · Connected</span>
    </div>

    {{-- Nav --}}
    <nav class="mt-4 flex-1 space-y-1 overflow-y-auto px-3 pb-4" aria-label="Main navigation">
        @php
            $userRole = strtolower(auth()->user()?->roles->first()?->name ?? auth()->user()?->role ?? 'staff');
            $navItems = [
                ['tab' => 'dashboard', 'label' => 'Dashboard', 'roles' => ['admin', 'staff', 'responder', 'guardian']],
                ['tab' => 'emergency', 'label' => 'Emergency Center', 'badge' => true, 'roles' => ['admin', 'staff', 'responder']],
                ['tab' => 'children', 'label' => 'Child Profiles', 'roles' => ['admin', 'staff', 'guardian', 'responder']],
                ['tab' => 'users', 'label' => 'User Management', 'roles' => ['admin']],
                ['tab' => 'reports', 'label' => 'Incident Reports', 'roles' => ['admin', 'staff', 'responder']],
                ['tab' => 'settings', 'label' => 'Settings & Support', 'roles' => ['admin', 'staff']],
            ];
        @endphp

        @foreach ($navItems as $item)
            @if(!auth()->check() || in_array($userRole, $item['roles']))
            <button
                type="button"
                @click="currentTab = '{{ $item['tab'] }}'; sidebarOpen = false"
                class="flex w-full min-h-[44px] items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition focus-ring"
                :class="currentTab === '{{ $item['tab'] }}' ? 'bg-brand-700 text-white' : 'text-slate-300 hover:bg-slate-700/60 hover:text-white'"
            >
                {{-- icon per module --}}
                @switch($item['tab'])
                    @case('dashboard')
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                        @break
                    @case('emergency')
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                        @break
                    @case('children')
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0114 0v1"/></svg>
                        @break
                    @case('users')
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                        @break
                    @case('reports')
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h6"/></svg>
                        @break
                    @case('settings')
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.6 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.6a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09c0 .68.39 1.3 1 1.51.62.26 1.34.14 1.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.4 9c.26.62.85 1 1.51 1H21a2 2 0 010 4h-.09c-.68 0-1.27.39-1.51 1z"/></svg>
                        @break
                @endswitch

                <span class="truncate">{{ $item['label'] }}</span>

                @if(!empty($item['badge']))
                    <span x-show="alertsCount > 0" x-cloak x-text="alertsCount"
                          class="ml-auto flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-critical-500 px-1 text-xs font-bold text-white"></span>
                @endif
            </button>
            @endif
        @endforeach
    </nav>

    {{-- Footer: admin + clock + sign out --}}
    <div class="border-t border-slate-700/60 px-4 py-4">
        <div class="mb-3 flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-700 text-sm font-semibold text-white"
                 x-text="currentUser.name ? currentUser.name.split(' ').map(n => n[0]).join('').slice(0,2).toUpperCase() : '—'"></div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium text-white" x-text="currentUser.name || 'Signed-in user'"></p>
                <p class="truncate text-xs text-slate-400" x-text="currentUser.role || ''"></p>
            </div>
        </div>
        <div class="mb-3 flex items-center justify-between rounded-lg bg-slate-700/40 px-3 py-2 text-xs text-slate-300">
            <span>Manila Time</span>
            <span class="font-mono tabular-nums text-slate-100" x-text="manilaTime"></span>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="w-full">
            @csrf
            <button type="submit" class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-lg border border-slate-600 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-700 focus-ring">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                Sign Out
            </button>
        </form>
    </div>
</aside>
