{{-- ============ TOP HEADER (LIGHT THEME) ============ --}}
<header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 py-3.5 backdrop-blur sm:px-6">
    
    {{-- Left: Mobile menu toggle + Page title --}}
    <div class="flex items-center gap-3 min-w-0">
        <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 lg:hidden focus-ring" @click="sidebarOpen = true" aria-label="Open menu">
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="min-w-0">
            <h1 class="truncate text-xl font-bold tracking-tight text-slate-900">
                <span x-show="currentTab === 'dashboard'">Dashboard</span>
                <span x-show="currentTab === 'emergency'">Emergency Center</span>
                <span x-show="currentTab === 'children'">Child Profiles</span>
                <span x-show="currentTab === 'users'">User Management</span>
                <span x-show="currentTab === 'reports'">Incident Reports</span>
                <span x-show="currentTab === 'settings'">Settings &amp; Support</span>
            </h1>
        </div>
    </div>

    {{-- Center & Right Controls (Search + Notifications + User Avatar + Action) --}}
    <div class="flex items-center gap-3">
        
        {{-- Search input --}}
        <div class="relative hidden md:block w-72">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 z-10">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
            </div>
            <input type="text"
                   x-model="childSearch"
                   @keydown.enter="if (childSearch.trim()) currentTab = 'children'"
                   placeholder="Search child, condition, zone..."
                   style="padding-left: 2.75rem !important; padding-right: 2rem !important; min-height: 38px;"
                   class="w-full rounded-xl border border-slate-200 bg-slate-50 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-brand-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-700/20 transition">
            <button type="button"
                    x-show="childSearch && childSearch.length > 0"
                    x-cloak
                    @click="childSearch = ''"
                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition"
                    title="Clear search">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Notifications Bell & Interactive Dropdown --}}
        <div class="relative" x-data="{ notificationsOpen: false }" @click.away="notificationsOpen = false" @keydown.escape.window="notificationsOpen = false">
            <button type="button"
                    @click="notificationsOpen = !notificationsOpen"
                    class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition focus-ring"
                    :class="{ 'bg-slate-100 text-slate-900 ring-2 ring-brand-700/20': notificationsOpen }"
                    aria-label="Notifications"
                    :aria-expanded="notificationsOpen">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 01-3.46 0"/>
                </svg>
                <span x-show="unreadNotifications > 0" x-cloak x-text="unreadNotifications"
                      class="absolute -right-1 -top-1 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-critical-500 px-1 text-[10px] font-bold text-white ring-2 ring-white animate-pulse"></span>
            </button>

            {{-- Dropdown Panel --}}
            <div x-show="notificationsOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                 class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl border border-slate-200 bg-white shadow-2xl z-50 overflow-hidden text-left">
                
                {{-- Dropdown Header --}}
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/80 px-4 py-3">
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm font-bold text-slate-900 tracking-tight">Notifications</h3>
                        <span x-show="unreadNotifications > 0" 
                              x-text="unreadNotifications + ' Active'" 
                              class="rounded-full bg-critical-50 border border-critical-200 px-2 py-0.5 text-[10px] font-bold text-critical-700"></span>
                        <span x-show="unreadNotifications === 0" 
                              class="rounded-full bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold text-emerald-700">All Clear</span>
                    </div>
                    <button type="button" 
                            @click="currentTab = 'emergency'; notificationsOpen = false"
                            class="text-xs font-semibold text-brand-700 hover:text-brand-800 hover:underline">
                        Emergency Center &rarr;
                    </button>
                </div>

                {{-- Notifications Content List --}}
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                    {{-- Active Emergencies (Pending & Responding) --}}
                    <template x-for="e in emergencies.filter(item => item.status !== 'resolved')" :key="'notif-' + e.id">
                        <div @click="currentTab = 'emergency'; notificationsOpen = false"
                             class="flex items-start gap-3 p-3.5 hover:bg-slate-50/90 cursor-pointer transition">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                                  :class="e.urgency === 'high' ? 'bg-critical-100 text-critical-600' : 'bg-amber-100 text-amber-600'">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                                    <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-1">
                                    <p class="text-xs font-bold text-slate-900 truncate" x-text="e.child ? e.child.name : 'Unknown Child'"></p>
                                    <span class="text-[10px] text-slate-400 font-mono shrink-0" x-text="e.reportedAt"></span>
                                </div>
                                <p class="text-xs text-slate-600 truncate mt-0.5">
                                    <span class="font-medium text-slate-800" x-text="e.type"></span> &bull; <span x-text="e.location"></span>
                                </p>
                                <span class="inline-block mt-1 text-[10px] font-semibold uppercase tracking-wider rounded px-1.5 py-0.5"
                                      :class="e.status === 'pending' ? 'bg-critical-50 text-critical-700 border border-critical-200' : 'bg-amber-50 text-amber-700 border border-amber-200'"
                                      x-text="e.status"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Activity / System Logs --}}
                    <template x-for="(act, idx) in activityLog.slice(0, 4)" :key="'act-' + idx">
                        <div class="flex items-start gap-3 p-3 text-xs hover:bg-slate-50 transition">
                            <span class="mt-1 h-2 w-2 rounded-full shrink-0"
                                  :class="{
                                      'bg-critical-500': act.tone === 'critical',
                                      'bg-amber-500': act.tone === 'warning',
                                      'bg-emerald-500': act.tone === 'success'
                                  }"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-slate-700 font-medium leading-relaxed text-[11px]" x-text="act.text"></p>
                                <span class="text-[10px] text-slate-400 font-mono mt-0.5 block" x-text="act.time"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Empty State when no active emergencies --}}
                    <div x-show="emergencies.filter(e => e.status !== 'resolved').length === 0 && activityLog.length === 0" 
                         class="p-6 text-center text-slate-500">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 mb-2">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="M9 12l2 2 4-4"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-800">All Clear</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">No active alerts requiring immediate response.</p>
                    </div>
                </div>

                {{-- Dropdown Footer --}}
                <div class="border-t border-slate-100 bg-slate-50/70 px-4 py-2.5 text-center">
                    <button type="button"
                            @click="currentTab = 'reports'; notificationsOpen = false"
                            class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                        View Complete Incident History &rarr;
                    </button>
                </div>
            </div>
        </div>

        {{-- User Avatar Initials --}}
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-700 text-xs font-bold text-white shadow-sm"
             style="background-color: #005B96;"
             x-text="currentUser.name ? currentUser.name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase() : 'AG'">
        </div>

        {{-- Live Telemetry / Action Badge --}}
        <div class="hidden sm:inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm">
            <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Live System</span>
        </div>
    </div>
</header>
