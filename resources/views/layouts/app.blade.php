<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · Parañaque CSN Alert System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.5/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eef5fb', 100: '#d6e6f4', 700: '#005B96', 800: '#004A7A', 900: '#003559'
                        },
                        critical: { 500: '#D9534F', 600: '#C9302C' },
                        warning: { 500: '#F59E0B' },
                        success: { 600: '#10B981' },
                    },
                    fontFamily: {
                        sans: ['"Inter"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .focus-ring:focus-visible { outline: 3px solid #005B96; outline-offset: 2px; }
    </style>
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased"
      x-data="csnApp()"
      x-init="init()"
      @keydown.escape.window="activeModal = null; sidebarOpen = false">

    {{-- ============ ROOT APP SHELL ============ --}}
    <div class="flex h-full">

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-cloak
             class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
             @click="sidebarOpen = false"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

        @include('partials.sidebar')

        {{-- Main column --}}
        <div class="flex flex-1 flex-col min-w-0">
            @include('partials.header')

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div x-show="currentTab === 'dashboard'" x-cloak>@include('modules.overview')</div>
                <div x-show="currentTab === 'emergency'" x-cloak>@include('modules.emergency-center')</div>
                <div x-show="currentTab === 'children'" x-cloak>@include('modules.child-profiles')</div>
                <div x-show="currentTab === 'users'" x-cloak>@include('modules.user-management')</div>
                <div x-show="currentTab === 'reports'" x-cloak>@include('modules.incident-reports')</div>
                <div x-show="currentTab === 'settings'" x-cloak>@include('modules.settings')</div>
            </main>
        </div>
    </div>

    <script>
        function csnApp() {
            return {
                // ---- navigation / shell state ----
                currentTab: 'dashboard',
                sidebarOpen: false,
                activeModal: null,
                activeChild: null,

                // ---- clock ----
                manilaTime: '',

                // ---- filters ----
                emergencyFilter: 'all',
                childSearch: '',

                // ---- data (populate from your controller) ----
                emergencies: @json($emergencies ?? []),
                activityLog: @json($activityLog ?? []),
                children: @json($children ?? []),
                users: @json($users ?? []),
                incidents: @json($incidents ?? []),

                // ---- current signed-in admin (populate from auth()->user()) ----
                currentUser: @json($currentUser ?? ['name' => '', 'role' => '']),

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                    // TODO: open a Laravel Reverb / Echo channel here for live updates, e.g.
                    // Echo.channel('emergencies').listen('EmergencyRaised', (e) => this.emergencies.unshift(e.emergency));
                },

                updateClock() {
                    this.manilaTime = new Intl.DateTimeFormat('en-PH', {
                        hour: '2-digit', minute: '2-digit', second: '2-digit',
                        timeZone: 'Asia/Manila'
                    }).format(new Date());
                },

                // ---- emergency actions ----
                // These currently mutate local state only. Wire each to a real
                // endpoint (fetch/axios) that updates the record server-side, e.g.:
                //
                //   async acknowledge(id) {
                //       await fetch(`/emergencies/${id}/acknowledge`, { method: 'PATCH', headers: {...} });
                //       const e = this.emergencies.find(e => e.id === id);
                //       if (e) e.status = 'responding';
                //   }
                acknowledge(id) {
                    const e = this.emergencies.find(e => e.id === id);
                    if (e) e.status = 'responding';
                },
                dispatch(id) {
                    const e = this.emergencies.find(e => e.id === id);
                    if (e) e.status = 'responding';
                },
                resolve(id) {
                    const e = this.emergencies.find(e => e.id === id);
                    if (e) e.status = 'resolved';
                },

                // ---- derived counters (computed from real data, not hardcoded) ----
                get alertsCount() {
                    return this.emergencies.filter(e => e.status !== 'resolved').length;
                },
                get respondingCount() {
                    return this.emergencies.filter(e => e.status === 'responding').length;
                },
                get resolvedToday() {
                    return this.emergencies.filter(e => e.status === 'resolved').length;
                },
                get enrolledChildren() {
                    return this.children.length;
                },
                get unreadNotifications() {
                    return this.alertsCount;
                },

                // ---- filtered views ----
                get filteredEmergencies() {
                    if (this.emergencyFilter === 'all') return this.emergencies;
                    if (this.emergencyFilter === 'high' || this.emergencyFilter === 'low') {
                        return this.emergencies.filter(e => e.urgency === this.emergencyFilter);
                    }
                    return this.emergencies.filter(e => e.status === this.emergencyFilter);
                },

                get filteredChildren() {
                    const q = this.childSearch.toLowerCase().trim();
                    if (!q) return this.children;
                    return this.children.filter(c =>
                        (c.name || '').toLowerCase().includes(q) ||
                        (c.condition || '').toLowerCase().includes(q)
                    );
                },
            }
        }
    </script>
</body>
</html>
