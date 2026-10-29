<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Parañaque CSN Alert System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/csn-logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <script src="https://cdn.tailwindcss.com"></script>
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
        #emergency-map { height: 400px !important; min-height: 400px !important; width: 100% !important; }
        .leaflet-popup-content-wrapper { background: #ffffff !important; color: #1e293b !important; border: 1px solid #e2e8f0 !important; border-radius: 0.75rem !important; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important; }
        .leaflet-popup-tip { background: #ffffff !important; }
        .leaflet-popup-content { margin: 12px 14px; line-height: 1.4; font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased"
      x-data="csnApp()"
      x-init="init()"
      @keydown.escape.window="activeModal = null; sidebarOpen = false; editUser = null">

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

            @php
                $userRole = strtolower(auth()->user()?->roles->first()?->name ?? auth()->user()?->role ?? 'admin');
            @endphp
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div x-show="currentTab === 'dashboard'" x-cloak>@include('modules.overview')</div>
                @if(!auth()->check() || in_array($userRole, ['superadmin', 'admin', 'responder']))
                    <div x-show="currentTab === 'emergency'" x-cloak>@include('modules.emergency-center')</div>
                @endif
                <div x-show="currentTab === 'children'" x-cloak>@include('modules.child-profiles')</div>
                @if(!auth()->check() || in_array($userRole, ['superadmin']))
                    <div x-show="currentTab === 'users'" x-cloak>@include('modules.user-management')</div>
                @endif
                @if(!auth()->check() || in_array($userRole, ['superadmin', 'admin', 'responder']))
                    <div x-show="currentTab === 'reports'" x-cloak>@include('modules.incident-reports')</div>
                @endif
                @if(!auth()->check() || in_array($userRole, ['superadmin', 'admin']))
                    <div x-show="currentTab === 'settings'" x-cloak>@include('modules.settings')</div>
                @endif
            </main>
        </div>
    </div>

    {{-- ============ CONFIRM LOGOUT MODAL ============ --}}
    <div x-show="activeModal === 'confirmLogout'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
             @click="activeModal = null"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        <div class="relative w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/10 text-center"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-100"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 ring-4 ring-rose-100/50 mb-4">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                    <path d="M16 17l5-5-5-5"/>
                    <path d="M21 12H9"/>
                </svg>
            </div>

            <h3 class="text-base font-bold text-slate-900">Sign Out of Command Center?</h3>
            <p class="mt-1.5 text-xs text-slate-500 leading-relaxed">
                You are currently signed in as <strong class="text-slate-800" x-text="currentUser.name || 'Admin'"></strong>. Your active session will be securely terminated.
            </p>

            <form method="POST" action="{{ route('logout') }}" class="mt-6 flex items-center justify-end gap-3">
                @csrf
                <button type="button"
                        @click="activeModal = null"
                        class="flex-1 min-h-[42px] rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus-ring transition">
                    Cancel
                </button>
                <button type="submit"
                        class="flex-1 min-h-[42px] rounded-xl bg-critical-500 px-4 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-critical-600 focus-ring transition flex items-center justify-center gap-1.5">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    <span>Sign Out</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        function csnApp() {
            return {
                // ---- navigation / shell state ----
                currentTab: 'dashboard',
                sidebarOpen: false,
                sidebarCollapsed: localStorage.getItem('csn_sidebar_collapsed') === 'true',
                activeModal: null,
                activeChild: null,

                // ---- clock ----
                manilaTime: '',
                nowTimestamp: Math.floor(Date.now() / 1000),

                // ---- filters ----
                emergencyFilter: 'all',
                childSearch: '',

                // ---- data (populate from your controller) ----
                emergencies: @json($emergencies ?? []),
                activityLog: @json($activityLog ?? []),
                auditLog: @json($auditLog ?? []),
                children: @json($children ?? []),
                users: @json($users ?? []),
                incidents: @json($incidents ?? []),
                announcements: @json($announcements ?? []),
                supportThreads: @json($supportThreads ?? []),

                // ---- live support chat state ----
                activeSupportGuardianId: null,
                supportSearch: '',
                supportReplyText: '',
                isSendingSupportReply: false,
                isPollingSupport: false,

                // ---- auto-sync heartbeat (polls every 4 seconds to detect active emergencies in real-time) ----
                autoSyncActive: true,
                isPollingEmergencies: false,
                lastSyncTime: 'Just now',

                // ---- current signed-in admin (populate from auth()->user()) ----
                currentUser: @json($currentUser ?? ['name' => '', 'role' => '']),
                settings: @json($settings ?? ['sound_on_high' => true, 'sound_on_low' => true]),

                // ---- dispatch modal state ----
                dispatchTarget: null,
                dispatchForm: {
                    responder_id: '',
                    barangay_unit: '',
                    barangay_phone: '',
                    eta: '8 mins',
                },
                isSubmittingDispatch: false,
                paranaqueGeoJSON: [],
                paranaqueBarangays: [
                    { name: 'Brgy. Baclaran Emergency Response Team', barangay: 'Baclaran', phone: '(02) 8832-1190', district: 'District 1', lat: 14.5312, lng: 120.9934, keywords: ['baclaran', 'quirino', 'taft', 'redemptorist', 'roxas blvd', 'bagong silang'] },
                    { name: 'Brgy. Don Galo Emergency Response Team', barangay: 'Don Galo', phone: '(02) 8851-2240', district: 'District 1', lat: 14.5022, lng: 120.9892, keywords: ['don galo', 'coastal', 'marina', 'elisco'] },
                    { name: 'Brgy. La Huerta Emergency Response Team', barangay: 'La Huerta', phone: '(02) 8826-3411', district: 'District 1', lat: 14.4947, lng: 120.9881, keywords: ['la huerta', 'dasa', 'kabihasnan', 'cathedral', 'bulungan'] },
                    { name: 'Brgy. San Dionisio Emergency Response Team', barangay: 'San Dionisio', phone: '(02) 8826-1144', district: 'District 1', lat: 14.4756, lng: 120.9928, keywords: ['san dionisio', 'evacom', 'lopez', 'amvel', 'waltermart sucat', 'huerta de san dionisio', 'tramo', 'valley 6'] },
                    { name: 'Brgy. San Isidro Emergency Response Team', barangay: 'San Isidro', phone: '(02) 8825-6012', district: 'District 1', lat: 14.4801, lng: 121.0085, keywords: ['san isidro', 'silverio', 'silverio compound', 'silverio comp', 'purok silverio', 'fortunata', 'fortunata village', 'greenheights', 'greenheights subdivision', 'clarmen', 'salvador', 'westmont', 'barikada', 'purok 1', 'purok 2', 'purok 3', 'purok 4', 'purok 5', 'purok 6', 'purok 7'] },
                    { name: 'Brgy. Santo Niño Emergency Response Team', barangay: 'Santo Niño', phone: '(02) 8852-4180', district: 'District 1', lat: 14.5052, lng: 121.0028, keywords: ['santo nino', 'santo niño', 'sto. nino', 'sto nino', 'multinational', 'col. e. de leon', 'naia', 'airport'] },
                    { name: 'Brgy. Tambo Emergency Response Team', barangay: 'Tambo', phone: '(02) 8853-7710', district: 'District 1', lat: 14.5150, lng: 120.9961, keywords: ['tambo', 'aseana', 'macapagal', 'mia road', 'entertainment city', 'okada', 'solaire', 'ayala malls manila bay'] },
                    { name: 'Brgy. Vitalez Emergency Response Team', barangay: 'Vitalez', phone: '(02) 8851-9032', district: 'District 1', lat: 14.5208, lng: 121.0003, keywords: ['vitalez', 'baltao', 'airmen', 'pildera', 'rivera'] },
                    { name: 'Brgy. BF Homes Emergency Response Team', barangay: 'BF Homes', phone: '(02) 8825-4411', district: 'District 2', lat: 14.4532, lng: 121.0189, keywords: ['bf homes', 'aguirre', 'president', 'elizalde', 'tahanan', 'teoville', 'concha cruz', 'masville'] },
                    { name: 'Brgy. Don Bosco Emergency Response Team', barangay: 'Don Bosco', phone: '(02) 8821-3190', district: 'District 2', lat: 14.4889, lng: 121.0381, keywords: ['don bosco', 'better living', 'doña soledad', 'dona soledad', 'levitown', 'aeropark', 'france', 'annex 35'] },
                    { name: 'Brgy. Marcelo Green Emergency Response Team', barangay: 'Marcelo Green', phone: '(02) 8821-5504', district: 'District 2', lat: 14.4691, lng: 121.0392, keywords: ['marcelo green', 'severina', 'west service', 'marcelo green village', 'sampaloc'] },
                    { name: 'Brgy. Merville Emergency Response Team', barangay: 'Merville', phone: '(02) 8824-8815', district: 'District 2', lat: 14.5098, lng: 121.0268, keywords: ['merville', 'moonwalk access', 'c5 extension', 'south admiral', 'nomi', 'camachile'] },
                    { name: 'Brgy. Moonwalk Emergency Response Team', barangay: 'Moonwalk', phone: '(02) 8821-9072', district: 'District 2', lat: 14.4981, lng: 121.0125, keywords: ['moonwalk', 'bricktown', 'kaingin', 'phimra', 'san agustin'] },
                    { name: 'Brgy. San Antonio Emergency Response Team', barangay: 'San Antonio', phone: '(02) 8820-7377', district: 'District 2', lat: 14.4826, lng: 121.0264, keywords: ['san antonio', 'valley 1', 'valley 2', 'city hall', 'fourth estate', 'san antonio valley'] },
                    { name: 'Brgy. San Martin de Porres Emergency Response Team', barangay: 'San Martin de Porres', phone: '(02) 8823-6120', district: 'District 2', lat: 14.4878, lng: 121.0489, keywords: ['san martin', 'united paranaque', 'ups 1', 'ups 2', 'ups 3', 'bicutan'] },
                    { name: 'Brgy. Sun Valley Emergency Response Team', barangay: 'Sun Valley', phone: '(02) 8823-4901', district: 'District 2', lat: 14.4921, lng: 121.0412, keywords: ['sun valley', 'cul de sac', 'annex 1', 'annex 2', 'annex 3', 'annex 4', 'annex 40'] },
                ],

                // ---- announcement composer state ----
                newAnnouncement: {
                    title: '',
                    body: '',
                },
                isPublishingAnnouncement: false,

                // ---- monthly statistics comparisons ----
                monthlyStats: Object.assign({
                    incidents: { percent: 0, formatted: '0% from last month', is_positive: true },
                    children: { percent: 0, formatted: '0% from last month', is_positive: true },
                    units: { percent: 0, formatted: '0% from last month', is_positive: true },
                    resolved: { percent: 0, formatted: '0% from last month', is_positive: true },
                }, @json($monthlyStats ?? [])),

                // ---- new emergency form state ----
                newEmergency: {
                    child_id: '',
                    type: 'medical_crisis',
                    urgency: 'high',
                    location: '',
                    lat: '',
                    lng: '',
                    guardian_notes: '',
                    responder_id: '',
                },
                isSubmittingEmergency: false,

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);

                    // Load Parañaque 16 Barangays official GeoJSON vector boundaries for Point-in-Polygon (PIP) dispatching
                    fetch('/data/paranaque-barangays.geojson')
                        .then(r => r.ok ? r.json() : null)
                        .then(data => {
                            if (data && data.features) {
                                this.paranaqueGeoJSON = data.features;
                            }
                        })
                        .catch(err => console.warn('GeoJSON boundary loading skipped:', err));

                    // Laravel Reverb / Echo real-time listener
                    if (window.Echo) {
                        window.Echo.private('emergencies')
                            .listen('EmergencyRaised', (e) => {
                                if (e && e.emergency) {
                                    const existingIndex = this.emergencies.findIndex(item => item.id === e.emergency.id);
                                    if (existingIndex === -1) {
                                        this.emergencies.unshift(e.emergency);
                                    } else {
                                        Object.assign(this.emergencies[existingIndex], e.emergency);
                                    }
                                    this.playAlertSound(e.emergency.urgency);
                                }
                            })
                            .listen('EmergencyCancelled', (e) => {
                                if (e && e.emergency) {
                                    const existingIndex = this.emergencies.findIndex(item => item.id === e.emergency.id);
                                    if (existingIndex !== -1) {
                                        Object.assign(this.emergencies[existingIndex], e.emergency);
                                    }
                                }
                            })
                            .listen('EmergencyLocationUpdated', (e) => {
                                const targetId = e?.emergency?.id || e?.id;
                                if (targetId) {
                                    const existing = this.emergencies.find(item => item.id === targetId);
                                    if (existing) {
                                        if (e.emergency) {
                                            Object.assign(existing, e.emergency);
                                        } else {
                                            if (e.lat !== undefined) existing.lat = e.lat;
                                            if (e.lng !== undefined) existing.lng = e.lng;
                                            if (e.location) existing.location = e.location;
                                            if (e.lat !== null && e.lng !== null) {
                                                const latDir = e.lat >= 0 ? 'N' : 'S';
                                                const lngDir = e.lng >= 0 ? 'E' : 'W';
                                                existing.coords = `${Math.abs(e.lat).toFixed(4)}° ${latDir}, ${Math.abs(e.lng).toFixed(4)}° ${lngDir}`;
                                            }
                                        }
                                    }
                                }
                            });
                    }

                    // Cloud Firestore Real-Time Sync Listener
                    if (window.FirebaseClient && typeof window.FirebaseClient.listenToEmergencies === 'function') {
                        window.FirebaseClient.listenToEmergencies((changeType, emergencyData) => {
                            if (!emergencyData || !emergencyData.id) return;
                            const existingIndex = this.emergencies.findIndex(item => item.id === emergencyData.id);

                            if (changeType === 'added') {
                                if (existingIndex === -1) {
                                    this.emergencies.unshift(emergencyData);
                                    this.playAlertSound(emergencyData.urgency || 'high');
                                } else {
                                    Object.assign(this.emergencies[existingIndex], emergencyData);
                                }
                            } else if (changeType === 'modified') {
                                if (existingIndex !== -1) {
                                    Object.assign(this.emergencies[existingIndex], emergencyData);
                                } else {
                                    this.emergencies.unshift(emergencyData);
                                }
                            } else if (changeType === 'removed') {
                                if (existingIndex !== -1) {
                                    this.emergencies.splice(existingIndex, 1);
                                }
                            }
                        });
                    }

                    // Start background auto-poll heartbeat (every 4 seconds)
                    this.startAutoPoll();

                    // Reset emergency center filter to 'all' whenever opening emergency center, or poll support threads when entering settings
                    this.$watch('currentTab', tab => {
                        if (tab === 'emergency') {
                            this.emergencyFilter = 'all';
                        }
                        if (tab === 'settings') {
                            this.ensureActiveSupportThread();
                            this.pollSupportThreads();
                        }
                    });

                    // Persist sidebar collapsed state
                    this.$watch('sidebarCollapsed', val => {
                        try {
                            localStorage.setItem('csn_sidebar_collapsed', val ? 'true' : 'false');
                        } catch (e) {}
                    });

                    this.ensureActiveSupportThread();
                },

                updateClock() {
                    this.nowTimestamp = Math.floor(Date.now() / 1000);
                    this.manilaTime = new Intl.DateTimeFormat('en-PH', {
                        hour: '2-digit', minute: '2-digit', second: '2-digit',
                        timeZone: 'Asia/Manila'
                    }).format(new Date());
                },

                // ---- live elapsed stopwatch & CSN de-escalation helpers ----
                getElapsedTimer(e) {
                    const _tick = this.nowTimestamp;
                    if (!e || !e.reportedAtTimestamp) {
                        return e?.reportedAt || 'Just now';
                    }
                    const diffSec = Math.max(0, _tick - Number(e.reportedAtTimestamp));
                    const hrs = Math.floor(diffSec / 3600);
                    const mins = Math.floor((diffSec % 3600) / 60);
                    const secs = diffSec % 60;
                    const pad = (n) => String(n).padStart(2, '0');
                    if (hrs > 0) {
                        return `${pad(hrs)}:${pad(mins)}:${pad(secs)}`;
                    }
                    return `${pad(mins)}:${pad(secs)}`;
                },

                getDeescalationProtocol(e) {
                    if (!e) return { badge: 'Standard CSN Protocol', sirenWarning: 'Silent approach recommended within 150m.', approachTip: 'Use calm voice and clear visual reassurance.' };
                    const cond = ((e.child && e.child.condition) ? e.child.condition : '').toLowerCase();
                    const rawType = (e.rawType || e.type || '').toLowerCase();
                    const fullChild = this.children.find(c => c.id === e.child_id || (e.child && c.id === e.child.id));
                    const clinicalNote = fullChild && fullChild.medical_notes ? this.formatMedicalNotes(fullChild.medical_notes) : null;

                    if (cond.includes('asd') || cond.includes('autis') || cond.includes('sensory') || rawType.includes('sensory')) {
                        return {
                            badge: 'Sensory-Safe / ASD Protocol',
                            sirenWarning: 'NO SIRENS / STROBES within 200m — Loud noise triggers severe sensory overload or bolting.',
                            approachTip: 'Speak in a low, calm voice. Maintain 2m space, avoid sudden touch, and use visual cues.',
                            clinicalNote: clinicalNote && clinicalNote !== 'No confidential medical notes recorded.' ? clinicalNote : null,
                        };
                    }
                    if (cond.includes('epilep') || cond.includes('seizure') || rawType.includes('seizure')) {
                        return {
                            badge: 'Seizure & Neurological Protocol',
                            sirenWarning: 'TURN OFF STROBE LIGHTS — Flashing emergency lights can trigger photosensitive seizures.',
                            approachTip: 'Clear surrounding hazards, cushion head, time the seizure, and do NOT restrain movements.',
                            clinicalNote: clinicalNote && clinicalNote !== 'No confidential medical notes recorded.' ? clinicalNote : null,
                        };
                    }
                    if (cond.includes('down') || cond.includes('intellectual') || rawType.includes('wandering')) {
                        return {
                            badge: 'Elopement & Reassurance Protocol',
                            sirenWarning: 'Silent approach advised — High-decibel sirens may cause the child to hide or run further.',
                            approachTip: 'Approach at eye level with open palms, use the child’s first name warmly, and offer a familiar item.',
                            clinicalNote: clinicalNote && clinicalNote !== 'No confidential medical notes recorded.' ? clinicalNote : null,
                        };
                    }
                    if (cond.includes('cerebral') || cond.includes('mobility') || cond.includes('orthopedic')) {
                        return {
                            badge: 'Mobility & Physical Support Protocol',
                            sirenWarning: 'Standard approach — Prepare accessible stretcher / wheelchair transport support.',
                            approachTip: 'Support posture carefully during transfer; check with guardian for spasticity or airway positioning.',
                            clinicalNote: clinicalNote && clinicalNote !== 'No confidential medical notes recorded.' ? clinicalNote : null,
                        };
                    }
                    return {
                        badge: 'CSN De-Escalation Protocol',
                        sirenWarning: 'Approach with low sirens within residential streets.',
                        approachTip: 'Assess sensory triggers, announce your presence gently, and consult the guardian immediately.',
                        clinicalNote: clinicalNote && clinicalNote !== 'No confidential medical notes recorded.' ? clinicalNote : null,
                    };
                },

                focusEmergencyOnMap(e) {
                    if (!e || e.lat === null || e.lng === null) return;

                    // 1. Switch to dashboard tab where the live map is situated
                    this.currentTab = 'dashboard';

                    // 2. If the target emergency is resolved, unhide resolved pins so it appears on the map
                    if (e.status === 'resolved' || e.status === 'cancelled') {
                        this.hideResolvedMap = false;
                        if (this.mapFilter === 'active' || this.mapFilter === 'high') {
                            this.mapFilter = 'all';
                        }
                    }

                    // 3. Smoothly scroll to the map and fly to the pin location
                    this.$nextTick(() => {
                        const mapEl = document.getElementById('emergency-map');
                        if (mapEl) {
                            mapEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }

                        const triggerFocus = () => {
                            if (typeof window.focusMapOnEmergency === 'function') {
                                window.focusMapOnEmergency(e);
                            }
                        };

                        triggerFocus();
                        setTimeout(triggerFocus, 200);
                        setTimeout(triggerFocus, 450);
                    });
                },

                async pollEmergencies(manual = false) {
                    if (this.isPollingEmergencies) return;
                    this.isPollingEmergencies = true;
                    try {
                        const res = await fetch('/emergencies', {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            }
                        });
                        if (res.ok) {
                            const latest = await res.json();
                            if (Array.isArray(latest)) {
                                const currentIds = new Set(this.emergencies.map(e => e.id));
                                const newArrivals = latest.filter(e => !currentIds.has(e.id));
                                
                                if (newArrivals.length > 0) {
                                    const topUrgency = newArrivals.some(e => e.urgency === 'high') ? 'high' : 'low';
                                    this.playAlertSound(topUrgency);
                                }

                                this.emergencies = latest;
                                this.lastSyncTime = new Intl.DateTimeFormat('en-PH', {
                                    hour: '2-digit', minute: '2-digit', second: '2-digit',
                                    timeZone: 'Asia/Manila'
                                }).format(new Date());
                            }
                        }
                    } catch (err) {
                        console.warn('[Auto-Sync] Polling error:', err);
                    } finally {
                        this.isPollingEmergencies = false;
                    }
                },

                startAutoPoll() {
                    // Polls every 4 seconds so incoming alerts from the mobile app update live without reloading
                    setInterval(() => {
                        if (this.autoSyncActive) {
                            this.pollEmergencies();
                            this.pollSupportThreads();
                        }
                    }, 4000);
                },

                ensureActiveSupportThread() {
                    if (!this.activeSupportGuardianId && this.supportThreads.length > 0) {
                        this.activeSupportGuardianId = this.supportThreads[0].guardian_id;
                    }
                },

                get unreadSupportCount() {
                    return (this.supportThreads || []).reduce((sum, t) => sum + (Number(t.unread_count) || 0), 0);
                },

                get filteredSupportThreads() {
                    const q = (this.supportSearch || '').toLowerCase().trim();
                    if (!q) return this.supportThreads || [];
                    return (this.supportThreads || []).filter(t =>
                        (t.guardian_name || '').toLowerCase().includes(q) ||
                        (t.child_name || '').toLowerCase().includes(q) ||
                        (t.guardian_phone || '').toLowerCase().includes(q) ||
                        (t.last_message_text || '').toLowerCase().includes(q)
                    );
                },

                get activeSupportThread() {
                    if (!this.supportThreads || this.supportThreads.length === 0) return null;
                    return this.supportThreads.find(t => t.guardian_id === this.activeSupportGuardianId) || this.supportThreads[0];
                },

                scrollSupportChatToBottom() {
                    setTimeout(() => {
                        const container = document.getElementById('support-chat-scroll');
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }, 60);
                },

                async selectSupportThread(guardianId) {
                    this.activeSupportGuardianId = guardianId;
                    this.scrollSupportChatToBottom();
                    const thread = this.supportThreads.find(t => t.guardian_id === guardianId);
                    if (thread && thread.unread_count > 0) {
                        thread.unread_count = 0;
                        try {
                            const res = await fetch(`/support/threads/${guardianId}/messages`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                                }
                            });
                            if (res.ok) {
                                const data = await res.json();
                                if (Array.isArray(data.messages)) {
                                    thread.messages = data.messages;
                                }
                            }
                        } catch (e) {
                            console.warn('[SupportChat] Could not mark messages read:', e);
                        }
                    }
                },

                async pollSupportThreads() {
                    if (this.isPollingSupport) return;
                    this.isPollingSupport = true;
                    try {
                        const res = await fetch('/support/threads', {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            }
                        });
                        if (res.ok) {
                            const data = await res.json();
                            if (Array.isArray(data.threads)) {
                                const prevCount = this.activeSupportThread ? (this.activeSupportThread.messages || []).length : 0;
                                this.supportThreads = data.threads;
                                this.ensureActiveSupportThread();
                                const newCount = this.activeSupportThread ? (this.activeSupportThread.messages || []).length : 0;
                                if (newCount > prevCount) {
                                    this.scrollSupportChatToBottom();
                                }
                            }
                        }
                    } catch (e) {
                        // Ignore background poll errors
                    } finally {
                        this.isPollingSupport = false;
                    }
                },

                async sendSupportReply() {
                    const thread = this.activeSupportThread;
                    const text = (this.supportReplyText || '').trim();
                    if (!thread || !text || this.isSendingSupportReply) return;

                    this.isSendingSupportReply = true;
                    try {
                        const res = await fetch(`/support/threads/${thread.guardian_id}/messages`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                            },
                            body: JSON.stringify({
                                text: text,
                                child_id: thread.child_id || null,
                            }),
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            alert(err.message || 'Could not send support reply.');
                            return;
                        }

                        const payload = await res.json();
                        if (payload && payload.data) {
                            if (!Array.isArray(thread.messages)) {
                                thread.messages = [];
                            }
                            thread.messages.push(payload.data);
                            thread.last_message_text = payload.data.text;
                            thread.last_message_sender = 'support';
                            thread.last_message_time = 'Just now';
                            thread.unread_count = 0;
                            this.supportReplyText = '';
                            this.scrollSupportChatToBottom();
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Network error while sending support reply.');
                    } finally {
                        this.isSendingSupportReply = false;
                    }
                },

                // ---- alert audio synthesizer ----
                playAlertSound(urgency = 'high', isPreview = false) {
                    const isHigh = urgency === 'high';
                    if (!isPreview && isHigh && !this.settings.sound_on_high) return;
                    if (!isPreview && !isHigh && !this.settings.sound_on_low) return;

                    try {
                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (!AudioCtx) return;
                        const ctx = new AudioCtx();

                        if (isHigh) {
                            // Pulsing critical alarm tone
                            [0, 0.16, 0.32].forEach((offset) => {
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.type = 'sawtooth';
                                osc.frequency.setValueAtTime(880, ctx.currentTime + offset);
                                osc.frequency.exponentialRampToValueAtTime(440, ctx.currentTime + offset + 0.12);
                                gain.gain.setValueAtTime(0.25, ctx.currentTime + offset);
                                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + offset + 0.12);
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.start(ctx.currentTime + offset);
                                osc.stop(ctx.currentTime + offset + 0.13);
                            });
                        } else {
                            // Gentle advisory chime
                            const osc = ctx.createOscillator();
                            const gain = ctx.createGain();
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                            gain.gain.setValueAtTime(0.2, ctx.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.4);
                            osc.connect(gain);
                            gain.connect(ctx.destination);
                            osc.start(ctx.currentTime);
                            osc.stop(ctx.currentTime + 0.4);
                        }
                    } catch (e) {
                        console.warn('Audio alert unavailable:', e);
                    }
                },

                // ---- emergency actions connected to real API endpoints ----
                async acknowledge(id) {
                    try {
                        const res = await fetch(`/emergencies/${id}/acknowledge`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                        });
                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            return alert(err.message || 'Could not update the alert. Please try again.');
                        }
                        const updated = await res.json();
                        const existing = this.emergencies.find(e => e.id === id);
                        if (existing) Object.assign(existing, updated);
                        this.emergencies = [...this.emergencies];
                        window.dispatchEvent(new CustomEvent('emergencies-updated'));
                    } catch (err) {
                        console.error(err);
                        alert('Network error while acknowledging alert.');
                    }
                },

                // ---- GIS GeoJSON Point-in-Polygon (PIP) ray-casting ----
                pointInPolygon(pt, polygon) {
                    const x = pt[0]; // lng
                    const y = pt[1]; // lat
                    let inside = false;
                    for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
                        const xi = polygon[i][0], yi = polygon[i][1];
                        const xj = polygon[j][0], yj = polygon[j][1];
                        const intersect = ((yi > y) !== (yj > y)) && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
                        if (intersect) inside = !inside;
                    }
                    return inside;
                },

                findBarangayByGeoJSON(lat, lng) {
                    if (!this.paranaqueGeoJSON || this.paranaqueGeoJSON.length === 0) return null;
                    const pt = [lng, lat]; // GeoJSON format: [longitude, latitude]

                    for (const f of this.paranaqueGeoJSON) {
                        const geom = f.geometry;
                        if (!geom) continue;
                        if (geom.type === 'Polygon' && geom.coordinates && geom.coordinates[0]) {
                            if (this.pointInPolygon(pt, geom.coordinates[0])) {
                                return f.properties;
                            }
                        } else if (geom.type === 'MultiPolygon' && geom.coordinates) {
                            for (const poly of geom.coordinates) {
                                if (poly && poly[0] && this.pointInPolygon(pt, poly[0])) {
                                    return f.properties;
                                }
                            }
                        }
                    }
                    return null;
                },

                detectAllocatedBarangay(emergency) {
                    if (!emergency) return { ...this.paranaqueBarangays[4], distanceKm: null, method: 'default' };

                    // Parse coordinates if available
                    let lat = (emergency.lat !== null && emergency.lat !== undefined && emergency.lat !== '') ? parseFloat(emergency.lat) : null;
                    let lng = (emergency.lng !== null && emergency.lng !== undefined && emergency.lng !== '') ? parseFloat(emergency.lng) : null;

                    if ((lat === null || isNaN(lat)) && emergency.location) {
                        const coordMatch = emergency.location.match(/(-?\d+\.\d+)[,\s]+(-?\d+\.\d+)/);
                        if (coordMatch) {
                            lat = parseFloat(coordMatch[1]);
                            lng = parseFloat(coordMatch[2]);
                        }
                    }

                    const calcDist = (bLat, bLng) => {
                        if (lat === null || lng === null || isNaN(lat) || isNaN(lng)) return null;
                        const R = 6371; // Earth radius in km
                        const dLat = (bLat - lat) * Math.PI / 180;
                        const dLon = (bLng - lng) * Math.PI / 180;
                        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                                  Math.cos(lat * Math.PI / 180) * Math.cos(bLat * Math.PI / 180) *
                                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
                        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                        return parseFloat((R * c).toFixed(2));
                    };

                    // TIER 1: Spatial Point-in-Polygon (PIP) with official Parañaque GeoJSON
                    if (lat !== null && lng !== null && !isNaN(lat) && !isNaN(lng)) {
                        const pipMatch = this.findBarangayByGeoJSON(lat, lng);
                        if (pipMatch) {
                            const matchedBrgy = this.paranaqueBarangays.find(b => b.barangay.toLowerCase() === pipMatch.barangay.toLowerCase());
                            const dist = (matchedBrgy && matchedBrgy.lat && matchedBrgy.lng)
                                ? calcDist(matchedBrgy.lat, matchedBrgy.lng)
                                : null;
                            return {
                                ...(matchedBrgy || {
                                    name: pipMatch.name,
                                    barangay: pipMatch.barangay,
                                    phone: pipMatch.phone,
                                    district: pipMatch.district,
                                }),
                                distanceKm: dist,
                                method: 'geojson_pip',
                            };
                        }
                    }

                    // TIER 2: Keyword scoring fallback
                    const fullChild = this.children.find(c => c.id === emergency.child_id || (emergency.child && c.id === emergency.child.id));
                    const searchStr = `${emergency.location || ''} ${fullChild?.address || ''} ${emergency.guardian_notes || emergency.guardianNotes || ''} ${emergency.responder?.name || ''}`.toLowerCase();

                    let bestKeywordMatch = null;
                    let highestScore = 0;

                    for (const brgy of this.paranaqueBarangays) {
                        let score = 0;
                        const bName = brgy.barangay.toLowerCase();
                        if (searchStr.includes(bName)) {
                            score += 15;
                        }
                        for (const kw of brgy.keywords) {
                            if (searchStr.includes(kw)) {
                                score += kw.length >= 8 ? 20 : 10;
                            }
                        }
                        if (score > highestScore) {
                            highestScore = score;
                            bestKeywordMatch = brgy;
                        }
                    }

                    if (bestKeywordMatch) {
                        const dist = (lat !== null && lng !== null && bestKeywordMatch.lat && bestKeywordMatch.lng)
                            ? calcDist(bestKeywordMatch.lat, bestKeywordMatch.lng)
                            : null;
                        return {
                            ...bestKeywordMatch,
                            distanceKm: dist,
                            method: 'keyword',
                        };
                    }

                    // TIER 3: Geodesic proximity fallback
                    let closestByDist = null;
                    let minDistance = Infinity;

                    if (lat !== null && lng !== null && !isNaN(lat) && !isNaN(lng)) {
                        for (const brgy of this.paranaqueBarangays) {
                            if (brgy.lat && brgy.lng) {
                                const dist = calcDist(brgy.lat, brgy.lng);
                                if (dist !== null && dist < minDistance) {
                                    minDistance = dist;
                                    closestByDist = { ...brgy, distanceKm: dist, method: 'proximity' };
                                }
                            }
                        }
                    }

                    if (closestByDist) {
                        return closestByDist;
                    }

                    // Fallback to Brgy. San Antonio (City Hall vicinity)
                    return { ...this.paranaqueBarangays[13], distanceKm: null, method: 'default' };
                },

                onBarangayUnitChange() {
                    const selected = this.paranaqueBarangays.find(b => b.name === this.dispatchForm.barangay_unit);
                    if (selected) {
                        this.dispatchForm.barangay_phone = selected.phone;
                    }
                    const matchingUser = this.users.find(u => (u.name || '').toLowerCase() === (this.dispatchForm.barangay_unit || '').toLowerCase());
                    this.dispatchForm.responder_id = matchingUser ? matchingUser.id : '';
                },

                openDispatchModal(emergency) {
                    this.dispatchTarget = emergency;
                    const allocated = this.detectAllocatedBarangay(emergency);
                    const currentUnitName = emergency.responder?.name && emergency.responder.name !== 'Unassigned'
                        ? emergency.responder.name
                        : allocated.name;
                    const matchedBrgy = this.paranaqueBarangays.find(b => b.name === currentUnitName) || allocated;
                    const matchingUser = this.users.find(u => (u.name || '').toLowerCase() === (currentUnitName || '').toLowerCase());

                    this.dispatchForm = {
                        responder_id: emergency.responderId || (matchingUser ? matchingUser.id : ''),
                        barangay_unit: currentUnitName,
                        barangay_phone: emergency.responder?.phone || matchedBrgy.phone || '(02) 8820-7377',
                        eta: emergency.eta || '8 mins',
                    };
                    this.activeModal = 'dispatchResponder';
                },

                async submitDispatch() {
                    if (!this.dispatchTarget) return;
                    this.isSubmittingDispatch = true;
                    try {
                        const payload = {
                            responder_id: this.dispatchForm.responder_id ? parseInt(this.dispatchForm.responder_id) : null,
                            barangay_unit: this.dispatchForm.barangay_unit || null,
                            barangay_phone: this.dispatchForm.barangay_phone || null,
                            eta: this.dispatchForm.eta || '8 mins',
                        };
                        const res = await fetch(`/emergencies/${this.dispatchTarget.id}/dispatch`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(payload),
                        });
                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            return alert(err.message || 'Could not dispatch responder. Please try again.');
                        }
                        const updated = await res.json();
                        const existing = this.emergencies.find(e => e.id === this.dispatchTarget.id);
                        if (existing) Object.assign(existing, updated);
                        this.emergencies = [...this.emergencies];
                        window.dispatchEvent(new CustomEvent('emergencies-updated'));
                        this.activeModal = null;
                        this.dispatchTarget = null;
                    } catch (err) {
                        console.error(err);
                        alert('Network error while dispatching responder.');
                    } finally {
                        this.isSubmittingDispatch = false;
                    }
                },

                async dispatch(id, responderId = null, eta = '8 mins') {
                    try {
                        const payload = {
                            responder_id: responderId,
                            eta: eta,
                        };
                        const res = await fetch(`/emergencies/${id}/dispatch`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(payload),
                        });
                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            return alert(err.message || 'Could not dispatch responder. Please try again.');
                        }
                        const updated = await res.json();
                        const existing = this.emergencies.find(e => e.id === id);
                        if (existing) Object.assign(existing, updated);
                        this.emergencies = [...this.emergencies];
                        window.dispatchEvent(new CustomEvent('emergencies-updated'));
                    } catch (err) {
                        console.error(err);
                        alert('Network error while dispatching responder.');
                    }
                },

                async resolve(id) {
                    try {
                        const res = await fetch(`/emergencies/${id}/resolve`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                        });
                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            return alert(err.message || 'Could not resolve the alert. Please try again.');
                        }
                        const updated = await res.json();
                        const existing = this.emergencies.find(e => e.id === id);
                        if (existing) Object.assign(existing, updated);
                        this.emergencies = [...this.emergencies];
                        window.dispatchEvent(new CustomEvent('emergencies-updated'));
                        this.fetchIncidents();
                    } catch (err) {
                        console.error(err);
                        alert('Network error while resolving alert.');
                    }
                },

                async submitEmergency() {
                    if (!this.newEmergency.child_id) {
                        alert('Please select a child profile.');
                        return;
                    }
                    if (!this.newEmergency.location.trim()) {
                        alert('Please specify the emergency location.');
                        return;
                    }

                    this.isSubmittingEmergency = true;
                    try {
                        const payload = {
                            child_id: this.newEmergency.child_id,
                            type: this.newEmergency.type,
                            urgency: this.newEmergency.urgency,
                            location: this.newEmergency.location.trim(),
                            guardian_notes: this.newEmergency.guardian_notes?.trim() || null,
                        };
                        if (this.newEmergency.lat) payload.lat = parseFloat(this.newEmergency.lat);
                        if (this.newEmergency.lng) payload.lng = parseFloat(this.newEmergency.lng);
                        if (this.newEmergency.responder_id) payload.responder_id = parseInt(this.newEmergency.responder_id);

                        const res = await fetch('/emergencies', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(payload),
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            alert(err.message || 'Could not log emergency alert.');
                            this.isSubmittingEmergency = false;
                            return;
                        }

                        const created = await res.json();
                        if (!this.emergencies.some(e => e.id === created.id)) {
                            this.emergencies.unshift(created);
                        }
                        this.playAlertSound(created.urgency);

                        // Reset modal form
                        this.newEmergency = {
                            child_id: '',
                            type: 'medical_crisis',
                            urgency: 'high',
                            location: '',
                            lat: '',
                            lng: '',
                            guardian_notes: '',
                            responder_id: '',
                        };
                        this.activeModal = null;
                    } catch (err) {
                        console.error(err);
                        alert('Network error while logging emergency alert.');
                    } finally {
                        this.isSubmittingEmergency = false;
                    }
                },

                // ---- announcement composer actions ----
                async publishAnnouncement() {
                    if (!this.newAnnouncement.title.trim() || !this.newAnnouncement.body.trim()) {
                        alert('Please provide both an announcement title and message content.');
                        return;
                    }
                    this.isPublishingAnnouncement = true;
                    try {
                        const res = await fetch('/announcements', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({
                                title: this.newAnnouncement.title.trim(),
                                body: this.newAnnouncement.body.trim(),
                            }),
                        });
                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            return alert(err.message || 'Could not publish announcement.');
                        }
                        const published = await res.json();
                        this.announcements.unshift(published);
                        this.newAnnouncement = { title: '', body: '' };
                        this.settingsSaveMsg = 'Announcement broadcasted successfully to all guardian devices!';
                        setTimeout(() => this.settingsSaveMsg = '', 5000);
                    } catch (err) {
                        console.error(err);
                        alert('Network error while publishing announcement.');
                    } finally {
                        this.isPublishingAnnouncement = false;
                    }
                },

                // ---- derived counters (computed from real data, not hardcoded) ----
                get alertsCount() {
                    return this.emergencies.filter(e => e.status !== 'resolved' && e.status !== 'cancelled').length;
                },
                get respondingCount() {
                    return this.emergencies.filter(e => e.status === 'responding').length;
                },
                get resolvedToday() {
                    const today = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila' }).format(new Date());
                    return this.emergencies.filter(e => e.status === 'resolved' && e.resolvedDate === today).length;
                },
                get enrolledChildren() {
                    return this.children.length;
                },
                get highUrgencyCount() {
                    return this.emergencies.filter(e => e.urgency === 'high' && e.status !== 'resolved' && e.status !== 'cancelled').length;
                },
                get lowUrgencyCount() {
                    return this.emergencies.filter(e => e.urgency === 'low' && e.status !== 'resolved' && e.status !== 'cancelled').length;
                },
                get unreadNotifications() {
                    return this.alertsCount;
                },
                get topActiveEmergency() {
                    const active = this.emergencies.filter(e => e.status !== 'resolved' && e.status !== 'cancelled');
                    if (active.length === 0) return null;
                    const high = active.find(e => e.urgency === 'high' && e.status === 'pending')
                        || active.find(e => e.urgency === 'high')
                        || active[0];
                    return high;
                },
                get incidentBreakdown() {
                    const total = this.emergencies.length;
                    const countType = (keys, terms) => this.emergencies.filter(e => {
                        const raw = (e.rawType || '').toLowerCase();
                        const lbl = (e.type || '').toLowerCase();
                        const matchesKey = keys.some(k => raw === k || raw.includes(k));
                        const matchesTerm = terms.some(t => lbl.includes(t));
                        return matchesKey || matchesTerm;
                    }).length;

                    const medical = countType(['medical_crisis', 'medical'], ['medical']);
                    const sensory = countType(['seizure_sensory', 'seizure', 'sensory'], ['seizure', 'sensory']);
                    const wandering = countType(['wandering', 'elopement'], ['wandering', 'elopement']);
                    const fire = countType(['fire'], ['fire', 'environmental']);

                    const calcPct = (count) => total > 0 ? Math.round((count / total) * 100) : 0;

                    return [
                        { key: 'medical_crisis', label: 'Medical Crisis', count: medical, pct: calcPct(medical), color: 'bg-rose-500', badge: 'bg-rose-50 text-rose-700 border-rose-200' },
                        { key: 'seizure_sensory', label: 'Seizure / Sensory Overload', count: sensory, pct: calcPct(sensory), color: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700 border-amber-200' },
                        { key: 'wandering', label: 'Wandering / Elopement', count: wandering, pct: calcPct(wandering), color: 'bg-sky-500', badge: 'bg-sky-50 text-sky-700 border-sky-200' },
                        { key: 'fire', label: 'Fire / Environmental', count: fire, pct: calcPct(fire), color: 'bg-orange-500', badge: 'bg-orange-50 text-orange-700 border-orange-200' },
                    ];
                },

                // ---- child profile actions ----
                isLoadingChild: false,
                isSavingChild: false,

                async viewChildProfile(childId) {
                    this.activeModal = 'viewChild';
                    this.isLoadingChild = true;
                    const local = this.children.find(c => c.id === childId);
                    if (local) this.activeChild = { ...local };

                    try {
                        const res = await fetch(`/children/${childId}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            }
                        });
                        if (!res.ok) {
                            alert('Unable to load child profile or unauthorized under DPA.');
                            return;
                        }
                        const full = await res.json();
                        this.activeChild = full;
                        const idx = this.children.findIndex(c => c.id === childId);
                        if (idx !== -1) Object.assign(this.children[idx], full);
                    } catch (err) {
                        console.error(err);
                        alert('Network error while loading child medical profile.');
                    } finally {
                        this.isLoadingChild = false;
                    }
                },

                formatMedicalNotes(notes) {
                    if (!notes) return 'No confidential medical notes recorded.';
                    const trimmed = String(notes).trim();
                    if (trimmed === 'No notes recorded.' || trimmed === 'No confidential medical notes recorded.') {
                        return 'No confidential medical notes recorded.';
                    }
                    if (trimmed.startsWith('{') && trimmed.endsWith('}')) {
                        try {
                            const parsed = JSON.parse(trimmed);
                            return parsed.medical_notes || parsed.notes || parsed.clinical_notes || 'No confidential medical notes recorded.';
                        } catch (e) {
                            return notes;
                        }
                    }
                    return notes;
                },

                async saveChildProfile(formElement) {
                    if (this.isSavingChild) return;
                    this.isSavingChild = true;
                    try {
                        const formData = new FormData(formElement);
                        const res = await fetch('/children', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: formData,
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            alert(err.message || 'Could not save child profile. Please check the inputs.');
                            return;
                        }

                        const saved = await res.json();
                        if (!this.children.some(c => c.id === saved.id)) {
                            this.children.unshift(saved);
                        }
                        this.activeModal = null;
                        formElement.reset();
                    } catch (err) {
                        console.error(err);
                        alert('Network error while saving child profile.');
                    } finally {
                        this.isSavingChild = false;
                    }
                },

                async searchChildrenServer() {
                    const q = this.childSearch.trim();
                    try {
                        const res = await fetch(`/children?q=${encodeURIComponent(q)}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            }
                        });
                        if (res.ok) {
                            this.children = await res.json();
                        }
                    } catch (err) {
                        console.error(err);
                    }
                },

                // ---- user management actions ----
                userSearch: '',
                newUser: {
                    name: '',
                    email: '',
                    role: 'admin',
                    phone: '',
                    status: 'active',
                    send_invite: true,
                    password: '',
                    child_ids: [],
                },
                isCreatingUser: false,
                isUpdatingUser: false,
                isSendingInvite: false,
                editUser: null,

                resetNewUser() {
                    this.newUser = {
                        name: '',
                        email: '',
                        role: 'admin',
                        phone: '',
                        status: 'active',
                        send_invite: true,
                        password: '',
                        child_ids: [],
                    };
                },

                openEditUser(user) {
                    this.editUser = JSON.parse(JSON.stringify(user));
                    if (!this.editUser.child_ids) {
                        this.editUser.child_ids = this.editUser.children ? this.editUser.children.map(c => c.id) : [];
                    }
                    this.activeModal = 'editUser';
                },

                async submitNewUser() {
                    if (!this.newUser.name.trim() || !this.newUser.email.trim()) {
                        alert('Please provide name and email.');
                        return;
                    }
                    this.isCreatingUser = true;
                    try {
                        const res = await fetch('/users', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(this.newUser),
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            const msg = err.errors ? Object.values(err.errors).flat().join('\n') : (err.message || 'Failed to create user. Please check your inputs.');
                            alert(msg);
                            this.isCreatingUser = false;
                            return;
                        }

                        const created = await res.json();
                        this.users.unshift(created);
                        alert(`User ${created.name} created! Invitation link has been sent to ${created.email}.`);
                        this.resetNewUser();
                        this.activeModal = null;
                    } catch (err) {
                        console.error(err);
                        alert('Network error while creating user.');
                    } finally {
                        this.isCreatingUser = false;
                    }
                },

                async saveUser(user) {
                    if (!user) return;
                    this.isUpdatingUser = true;
                    try {
                        const res = await fetch(`/users/${user.id}`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({
                                name: user.name,
                                email: user.email,
                                phone: user.phone,
                                role: user.role,
                                status: user.status,
                                is_active: user.status === 'active',
                                child_ids: user.child_ids || [],
                            }),
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            alert(err.message || 'Failed to update user.');
                            this.isUpdatingUser = false;
                            return;
                        }

                        const updated = await res.json();
                        const idx = this.users.findIndex(u => u.id === user.id);
                        if (idx !== -1) Object.assign(this.users[idx], updated);
                        this.editUser = null;
                        this.activeModal = null;
                    } catch (err) {
                        console.error(err);
                        alert('Network error while saving user changes.');
                    } finally {
                        this.isUpdatingUser = false;
                    }
                },

                async resendInvite(user) {
                    this.isSendingInvite = true;
                    try {
                        const res = await fetch(`/users/${user.id}/invite`, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            alert(err.message || 'Failed to send invitation link.');
                            return;
                        }

                        const data = await res.json();
                        alert(data.message || 'Invitation sent successfully.');
                    } catch (err) {
                        console.error(err);
                        alert('Network error sending invitation.');
                    } finally {
                        this.isSendingInvite = false;
                    }
                },

                // ---- incident reporting state and actions ----
                incidentStartDate: '',
                incidentEndDate: '',
                incidentType: 'all',
                incidentSearch: '',
                isFilteringIncidents: false,
                editingSummaryIncident: null,
                isSavingSummary: false,
                exportToast: '',

                setIncidentPreset(preset) {
                    const today = new Date();
                    const pad = (n) => String(n).padStart(2, '0');
                    const toYmd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

                    if (preset === 'today') {
                        this.incidentStartDate = toYmd(today);
                        this.incidentEndDate = toYmd(today);
                    } else if (preset === 'this_month') {
                        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                        this.incidentStartDate = toYmd(firstDay);
                        this.incidentEndDate = toYmd(today);
                    } else if (preset === 'last_30') {
                        const past = new Date();
                        past.setDate(today.getDate() - 30);
                        this.incidentStartDate = toYmd(past);
                        this.incidentEndDate = toYmd(today);
                    } else {
                        this.incidentStartDate = '';
                        this.incidentEndDate = '';
                    }
                    this.fetchIncidents();
                },

                async fetchIncidents() {
                    this.isFilteringIncidents = true;
                    try {
                        const params = new URLSearchParams();
                        if (this.incidentStartDate) params.append('start_date', this.incidentStartDate);
                        if (this.incidentEndDate) params.append('end_date', this.incidentEndDate);
                        if (this.incidentType && this.incidentType !== 'all') params.append('type', this.incidentType);
                        if (this.incidentSearch.trim()) params.append('q', this.incidentSearch.trim());

                        const qs = params.toString() ? '?' + params.toString() : '';
                        const res = await fetch(`/incident-reports${qs}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            }
                        });
                        if (res.ok) {
                            this.incidents = await res.json();
                        }
                    } catch (err) {
                        console.error(err);
                    } finally {
                        this.isFilteringIncidents = false;
                    }
                },

                triggerExport(format) {
                    const params = new URLSearchParams();
                    if (this.incidentStartDate) params.append('start_date', this.incidentStartDate);
                    if (this.incidentEndDate) params.append('end_date', this.incidentEndDate);
                    if (this.incidentType && this.incidentType !== 'all') params.append('type', this.incidentType);
                    if (this.incidentSearch.trim()) params.append('q', this.incidentSearch.trim());

                    const qs = params.toString() ? '?' + params.toString() : '';
                    const url = format === 'pdf' ? `/incident-reports/export-pdf${qs}` : `/incident-reports/export-csv${qs}`;

                    window.location = url;
                    this.exportToast = `${format.toUpperCase()} export started. Your download will appear shortly.`;
                    setTimeout(() => this.exportToast = '', 4500);
                },

                openEditSummary(inc) {
                    this.editingSummaryIncident = {
                        ...inc,
                        incident_summary: inc.raw_summary || (inc.incident_summary === 'No summary filed yet.' ? '' : inc.incident_summary),
                    };
                    this.activeModal = 'editSummary';
                },

                async saveIncidentSummary() {
                    if (!this.editingSummaryIncident) return;
                    this.isSavingSummary = true;
                    try {
                        const res = await fetch(`/incident-reports/${this.editingSummaryIncident.raw_id}/summary`, {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify({
                                incident_summary: this.editingSummaryIncident.incident_summary,
                            }),
                        });

                        if (!res.ok) {
                            alert('Could not update incident summary.');
                            return;
                        }

                        const data = await res.json();
                        const idx = this.incidents.findIndex(i => i.raw_id === this.editingSummaryIncident.raw_id);
                        if (idx !== -1) {
                            this.incidents[idx].incident_summary = data.incident.incident_summary;
                            this.incidents[idx].raw_summary = data.incident.raw_summary;
                        }
                        this.editingSummaryIncident = null;
                        this.activeModal = null;
                    } catch (err) {
                        console.error(err);
                        alert('Network error while saving incident summary.');
                    } finally {
                        this.isSavingSummary = false;
                    }
                },

                // ---- system settings actions ----
                isSavingSettings: false,
                settingsSaveMsg: '',
                supportTicket: {
                    subject: '',
                    category: 'technical',
                    message: '',
                },
                isSubmittingSupport: false,
                supportToast: '',

                async saveSettings(overrides = {}) {
                    this.isSavingSettings = true;
                    try {
                        const payload = {
                            sound_on_high: Boolean(this.settings.sound_on_high),
                            sound_on_low: Boolean(this.settings.sound_on_low),
                            hotline_pdrrmo: this.settings.hotline_pdrrmo || '',
                            hotline_ambulance: this.settings.hotline_ambulance || '',
                            ...overrides,
                        };

                        const res = await fetch('/settings', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            },
                            body: JSON.stringify(payload),
                        });

                        if (!res.ok) {
                            const err = await res.json().catch(() => ({}));
                            alert(err.message || 'Failed to save configuration settings.');
                            return;
                        }

                        const data = await res.json();
                        Object.assign(this.settings, data.settings);
                        this.settingsSaveMsg = 'System configuration saved successfully.';
                        setTimeout(() => this.settingsSaveMsg = '', 3500);
                    } catch (err) {
                        console.error(err);
                        alert('Network error while saving settings.');
                    } finally {
                        this.isSavingSettings = false;
                    }
                },

                async toggleSoundSetting(key) {
                    this.settings[key] = !this.settings[key];
                    await this.saveSettings({ [key]: this.settings[key] });
                },

                submitSupportTicket() {
                    if (!this.supportTicket.subject.trim() || !this.supportTicket.message.trim()) {
                        alert('Please fill out the subject and message.');
                        return;
                    }
                    this.isSubmittingSupport = true;
                    setTimeout(() => {
                        this.isSubmittingSupport = false;
                        this.supportToast = 'Support ticket submitted to CSN Systems Administration (Ticket #' + Math.floor(1000 + Math.random() * 9000) + ').';
                        this.supportTicket = { subject: '', category: 'technical', message: '' };
                        this.activeModal = null;
                        setTimeout(() => this.supportToast = '', 5000);
                    }, 500);
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
                        (c.condition || '').toLowerCase().includes(q) ||
                        (c.address || '').toLowerCase().includes(q)
                    );
                },

                get filteredUsers() {
                    const q = this.userSearch.toLowerCase().trim();
                    if (!q) return this.users;
                    return this.users.filter(u =>
                        (u.name || '').toLowerCase().includes(q) ||
                        (u.email || '').toLowerCase().includes(q) ||
                        (u.phone || '').toLowerCase().includes(q) ||
                        (u.role || '').toLowerCase().includes(q)
                    );
                },
            }
        }

        // Prevent browser back-button showing cached authenticated page after logout
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</body>
</html>
