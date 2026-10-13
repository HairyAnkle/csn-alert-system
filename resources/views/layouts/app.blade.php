<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · Parañaque CSN Alert System</title>
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
                $userRole = strtolower(auth()->user()?->roles->first()?->name ?? auth()->user()?->role ?? 'staff');
            @endphp
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <div x-show="currentTab === 'dashboard'" x-cloak>@include('modules.overview')</div>
                @if(!auth()->check() || in_array($userRole, ['admin', 'staff', 'responder']))
                    <div x-show="currentTab === 'emergency'" x-cloak>@include('modules.emergency-center')</div>
                @endif
                <div x-show="currentTab === 'children'" x-cloak>@include('modules.child-profiles')</div>
                @if(!auth()->check() || in_array($userRole, ['admin']))
                    <div x-show="currentTab === 'users'" x-cloak>@include('modules.user-management')</div>
                @endif
                @if(!auth()->check() || in_array($userRole, ['admin', 'staff', 'responder']))
                    <div x-show="currentTab === 'reports'" x-cloak>@include('modules.incident-reports')</div>
                @endif
                @if(!auth()->check() || in_array($userRole, ['admin', 'staff']))
                    <div x-show="currentTab === 'settings'" x-cloak>@include('modules.settings')</div>
                @endif
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
                auditLog: @json($auditLog ?? []),
                children: @json($children ?? []),
                users: @json($users ?? []),
                incidents: @json($incidents ?? []),
                announcements: @json($announcements ?? []),

                // ---- current signed-in admin (populate from auth()->user()) ----
                currentUser: @json($currentUser ?? ['name' => '', 'role' => '']),
                settings: @json($settings ?? ['sound_on_high' => true, 'sound_on_low' => true]),

                // ---- dispatch modal state ----
                dispatchTarget: null,
                dispatchForm: {
                    responder_id: '',
                    eta: '8 mins',
                },
                isSubmittingDispatch: false,

                // ---- announcement composer state ----
                newAnnouncement: {
                    title: '',
                    body: '',
                },
                isPublishingAnnouncement: false,

                // ---- monthly statistics comparisons ----
                monthlyStats: Object.assign({
                    incidents: { percent: 5.23, formatted: '+5.23% total incidents from last month', is_positive: true },
                    children: { percent: 8.50, formatted: '+8.50% from last month', is_positive: true },
                    units: { percent: 2.10, formatted: '+2.10% from last month', is_positive: true },
                    resolved: { percent: 14.20, formatted: '+14.20% from last month', is_positive: true },
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
                },

                updateClock() {
                    this.manilaTime = new Intl.DateTimeFormat('en-PH', {
                        hour: '2-digit', minute: '2-digit', second: '2-digit',
                        timeZone: 'Asia/Manila'
                    }).format(new Date());
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
                    } catch (err) {
                        console.error(err);
                        alert('Network error while acknowledging alert.');
                    }
                },

                openDispatchModal(emergency) {
                    this.dispatchTarget = emergency;
                    const responders = this.users.filter(u => (u.role || '').toLowerCase() === 'responder');
                    this.dispatchForm = {
                        responder_id: emergency.responderId || (responders.length > 0 ? responders[0].id : ''),
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
                    return this.emergencies.filter(e => e.status === 'resolved').length;
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
                    role: 'staff',
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
                        role: 'staff',
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
                            alert(err.message || 'Failed to create user. Please check your inputs.');
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
