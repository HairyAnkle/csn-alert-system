{{-- ============ SIDEBAR ============ --}}
<aside
    class="relative fixed inset-y-0 left-0 z-40 flex flex-col bg-slate-800 text-slate-100 transition-all duration-200 ease-in-out lg:static lg:translate-x-0"
    :class="[
        sidebarOpen ? 'translate-x-0' : '-translate-x-full',
        sidebarCollapsed ? 'w-72 lg:w-20' : 'w-72 lg:w-72'
    ]"
>
    {{-- Single Floating Collapse / Expand Button on Top-Right Border of Sidebar --}}
    <button
        type="button"
        @click="sidebarCollapsed = !sidebarCollapsed"
        class="hidden lg:flex absolute -right-3 top-6 z-50 h-6 w-6 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-slate-300 shadow-md hover:bg-brand-700 hover:border-brand-500 hover:text-white transition focus-ring"
        :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        aria-label="Toggle sidebar"
    >
        <svg class="h-3.5 w-3.5 shrink-0 transition-transform duration-200" :class="sidebarCollapsed ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
    </button>

    {{-- Branding --}}
    <div class="flex items-center py-5 border-b border-slate-700/60 transition-all"
         :class="sidebarCollapsed ? 'lg:justify-center px-3' : 'px-5 gap-3'">
        {{-- Logo Icon --}}
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white p-1 ring-2 ring-white/20 shadow-sm">
            <img src="{{ asset('images/csn-logo.png') }}" alt="Parañaque CSN Logo" class="h-full w-full object-contain">
        </div>

        {{-- Brand Text (Only shown when expanded) --}}
        <div class="min-w-0 flex-1" x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
            <p class="truncate text-sm font-semibold leading-tight text-white">Parañaque CSN</p>
            <p class="truncate text-xs text-slate-400">Emergency Alert System</p>
        </div>

        {{-- Mobile close button --}}
        <button class="ml-auto rounded-md p-1.5 text-slate-400 hover:bg-slate-700 lg:hidden focus-ring" @click="sidebarOpen = false" aria-label="Close menu">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
        </button>
    </div>

    {{-- Live connection badge --}}
    <div class="mt-4 flex items-center rounded-lg bg-slate-700/50 py-2.5 transition-all"
         :class="sidebarCollapsed ? 'lg:justify-center lg:mx-3 lg:px-0 mx-4 px-3 gap-2' : 'mx-4 px-3 gap-2'"
         :title="sidebarCollapsed ? 'Live Dispatch · Connected' : ''">
        <span class="relative flex h-2.5 w-2.5 shrink-0">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
        </span>
        <span class="text-xs font-medium text-slate-200 truncate" x-show="!sidebarCollapsed" x-transition>Live Dispatch · Connected</span>
    </div>

    {{-- Nav --}}
    <nav class="mt-4 flex-1 space-y-1.5 overflow-y-auto px-2 pb-4" aria-label="Main navigation">
        @php
            $userRole = strtolower(auth()->user()?->roles->first()?->name ?? auth()->user()?->role ?? 'admin');
            $navItems = [
                ['tab' => 'dashboard', 'label' => 'Dashboard', 'roles' => ['superadmin', 'admin', 'responder', 'guardian']],
                ['tab' => 'emergency', 'label' => 'Emergency Center', 'badge' => true, 'roles' => ['superadmin', 'admin', 'responder']],
                ['tab' => 'children', 'label' => 'Child Profiles', 'roles' => ['superadmin', 'admin', 'guardian', 'responder']],
                ['tab' => 'users', 'label' => 'User Management', 'roles' => ['superadmin']],
                ['tab' => 'reports', 'label' => 'Incident Reports', 'roles' => ['superadmin', 'admin', 'responder']],
                ['tab' => 'settings', 'label' => 'Settings & Support', 'roles' => ['superadmin', 'admin']],
            ];
        @endphp

        @foreach ($navItems as $item)
            @if(!auth()->check() || in_array($userRole, $item['roles']))
            <button
                type="button"
                @click="currentTab = '{{ $item['tab'] }}'; if ('{{ $item['tab'] }}' === 'emergency') { emergencyFilter = 'all'; } sidebarOpen = false"
                class="group relative flex w-full min-h-[44px] items-center rounded-xl transition focus-ring"
                :class="[
                    sidebarCollapsed ? 'lg:justify-center lg:px-0 px-3 gap-3' : 'px-3 gap-3',
                    currentTab === '{{ $item['tab'] }}' ? 'bg-brand-700 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-700/60 hover:text-white'
                ]"
                :title="sidebarCollapsed ? '{{ $item['label'] }}' : ''"
            >
                {{-- icon per module --}}
                <div class="relative shrink-0 flex items-center justify-center">
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

                    @if(!empty($item['badge']))
                        <span x-show="alertsCount > 0" x-cloak
                              class="absolute -top-1 -right-1 flex h-2.5 w-2.5 rounded-full bg-critical-500 ring-2 ring-slate-800"
                              :class="sidebarCollapsed ? 'lg:block' : 'lg:hidden'"></span>
                    @endif
                    @if($item['tab'] === 'settings')
                        <span x-show="unreadSupportCount > 0" x-cloak
                              class="absolute -top-1 -right-1 flex h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-slate-800"
                              :class="sidebarCollapsed ? 'lg:block' : 'lg:hidden'"></span>
                    @endif
                </div>

                <span class="truncate text-sm font-medium" x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">{{ $item['label'] }}</span>

                @if(!empty($item['badge']))
                    <span x-show="alertsCount > 0 && !sidebarCollapsed" x-cloak x-text="alertsCount"
                          class="ml-auto flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-critical-500 px-1 text-xs font-bold text-white"></span>
                @endif
                @if($item['tab'] === 'settings')
                    <span x-show="unreadSupportCount > 0 && !sidebarCollapsed" x-cloak x-text="unreadSupportCount"
                          class="ml-auto flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-emerald-600 px-1 text-xs font-bold text-white"></span>
                @endif
            </button>
            @endif
        @endforeach
    </nav>

    {{-- Footer: admin + clock + sign out --}}
    <div class="border-t border-slate-700/60 p-3 transition-all"
         :class="sidebarCollapsed ? 'lg:px-2' : 'px-4 py-4'">
        <div class="mb-3 flex items-center transition-all"
             :class="sidebarCollapsed ? 'lg:justify-center' : 'gap-3'"
             :title="sidebarCollapsed ? ((currentUser.name || 'Signed-in user') + ' (' + (currentUser.role || '') + ')') : ''">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-700 text-sm font-semibold text-white ring-1 ring-white/10"
                 x-text="currentUser.name ? currentUser.name.split(' ').map(n => n[0]).join('').slice(0,2).toUpperCase() : '—'"></div>
            <div class="min-w-0 flex-1" x-show="!sidebarCollapsed" x-transition>
                <p class="truncate text-sm font-medium text-white" x-text="currentUser.name || 'Signed-in user'"></p>
                <p class="truncate text-xs text-slate-400 capitalize" x-text="currentUser.role || ''"></p>
            </div>
        </div>
        <div class="mb-3 flex items-center justify-between rounded-lg bg-slate-700/40 px-3 py-2 text-xs text-slate-300"
             x-show="!sidebarCollapsed" x-transition>
            <span>Manila Time</span>
            <span class="font-mono tabular-nums text-slate-100" x-text="manilaTime"></span>
        </div>
        <button type="button" @click="activeModal = 'confirmLogout'"
                class="flex min-h-[44px] w-full items-center justify-center rounded-lg border border-slate-600 text-sm font-medium text-slate-200 hover:bg-slate-700 focus-ring transition"
                :class="sidebarCollapsed ? 'lg:px-0 lg:py-2 px-3 py-2 gap-2' : 'px-3 py-2 gap-2'"
                :title="sidebarCollapsed ? 'Sign Out' : ''">
            <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
            <span x-show="!sidebarCollapsed" x-transition>Sign Out</span>
        </button>
    </div>
</aside>