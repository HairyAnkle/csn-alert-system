{{-- ============ TOP HEADER ============ --}}
<header class="sticky top-0 z-20 flex items-center gap-3 border-b border-slate-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6">
    <button type="button" class="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden focus-ring" @click="sidebarOpen = true" aria-label="Open menu">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    {{-- Breadcrumb --}}
    <div class="min-w-0 flex-1">
        <p class="text-xs text-slate-400">Parañaque CSN Alert System</p>
        <h1 class="truncate text-lg font-semibold text-slate-800">
            <span x-show="currentTab === 'dashboard'">Dashboard Overview</span>
            <span x-show="currentTab === 'emergency'">Emergency Center</span>
            <span x-show="currentTab === 'children'">Child Profiles</span>
            <span x-show="currentTab === 'users'">User Management</span>
            <span x-show="currentTab === 'reports'">Incident Reports</span>
            <span x-show="currentTab === 'settings'">Settings &amp; Support</span>
        </h1>
    </div>

    {{-- Notifications --}}
    <div class="relative">
        <button type="button" class="relative inline-flex h-11 w-11 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 focus-ring" aria-label="Notifications">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
            <span x-show="unreadNotifications > 0" x-cloak x-text="unreadNotifications"
                  class="absolute -right-0.5 -top-0.5 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-critical-500 px-1 text-[11px] font-bold text-white ring-2 ring-white"></span>
        </button>
    </div>
</header>
