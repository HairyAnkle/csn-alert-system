{{-- ============ MODULE 1: DASHBOARD OVERVIEW (LIGHT THEME WITH CARD STRUCTURE MATCHING ATTACHMENT) ============ --}}
<div class="space-y-6">

    {{-- Top Greeting --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Welcome back, <span x-text="currentUser.name ? currentUser.name.split(' ')[0] : 'Administrator'"></span></span>
                <span>👋</span>
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">Parañaque CSN Emergency Alert &amp; Monitoring Dashboard</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>PDRRMO Active · 24/7 Monitoring</span>
            </span>
        </div>
    </div>

    {{-- STICKY ACTIVE CRISIS BANNER (Table 1.14 Active Alerts Ticker) --}}
    <template x-if="alertsCount > 0 && topActiveEmergency">
        <div class="rounded-2xl border-2 border-critical-500/70 bg-gradient-to-r from-rose-50 via-white to-rose-50/70 p-4 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                <div class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-critical-500 text-white shadow-sm">
                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-rose-600"></span>
                    </span>
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-critical-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">Active Crisis Alert</span>
                        <span class="font-bold text-slate-900 text-sm truncate" x-text="(topActiveEmergency.child?.name || 'Child SOS') + ' · ' + topActiveEmergency.type"></span>
                        <span class="inline-flex items-center gap-1 rounded-md bg-rose-100 border border-rose-200 px-2 py-0.5 text-xs font-mono font-bold text-rose-800">
                            <span>⏱</span>
                            <span x-text="getElapsedTimer(topActiveEmergency)"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 mt-1 truncate">
                        <span class="font-semibold text-brand-800" x-text="topActiveEmergency.child?.condition || 'CSN Profile'"></span>
                        <span> · 📍 </span>
                        <span class="font-medium" x-text="topActiveEmergency.location"></span>
                        <span class="text-rose-700 font-semibold ml-1" x-text="'— ' + getDeescalationProtocol(topActiveEmergency).sirenWarning"></span>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button x-show="topActiveEmergency.lat !== null && topActiveEmergency.lng !== null"
                        type="button"
                        @click="focusEmergencyOnMap(topActiveEmergency)"
                        class="min-h-[38px] inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition focus-ring">
                    <svg class="h-3.5 w-3.5 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
                    <span>Focus Pin</span>
                </button>
                <button type="button"
                        @click="currentTab = 'emergency'"
                        class="min-h-[38px] inline-flex items-center gap-1.5 rounded-xl bg-critical-500 px-4 text-xs font-semibold text-white shadow-sm hover:bg-critical-600 transition focus-ring">
                    <span>View &amp; Dispatch</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </template>

    {{-- TOP ROW: Hero Card (Left, wider) + 3 Stacked Cards (Right, compact) (Matching Attachment Layout) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        {{-- LEFT HERO CARD (Wider ~60% column, matching "Revenue $48,250" card in attachment) --}}
        <div class="lg:col-span-7 xl:col-span-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div>
                {{-- Header inside Hero Card --}}
                <div class="flex items-center justify-between">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Emergencies</p>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full transition"
                          :class="alertsCount > 0 ? 'bg-critical-50 text-critical-700 border border-critical-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                        <span class="h-1.5 w-1.5 rounded-full" :class="alertsCount > 0 ? 'bg-critical-500 animate-ping' : 'bg-emerald-500'"></span>
                        <span x-text="alertsCount > 0 ? 'Action Required' : 'All Clear'"></span>
                    </span>
                </div>

                {{-- Big Stat Number --}}
                <div class="mt-3">
                    <p class="text-5xl font-extrabold tracking-tight text-slate-900 font-mono" x-text="alertsCount"></p>
                </div>

                {{-- Pill Badge (matching "+5.23% total incidents from last month") --}}
                <div class="mt-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
                          :class="monthlyStats.incidents.is_positive ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'">
                        <svg x-show="monthlyStats.incidents.is_positive" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        <svg x-show="!monthlyStats.incidents.is_positive" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                        <span x-text="monthlyStats.incidents.formatted"></span>
                    </span>
                </div>
            </div>

            {{-- Divider line & Bottom Split Sub-Metrics (Matching "Subscriptions $41,900" and "One-time $6,350") --}}
            <div class="mt-6 pt-5 border-t border-slate-100">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">High Urgency</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight font-mono"
                           :class="highUrgencyCount > 0 ? 'text-critical-600' : 'text-slate-900'"
                           x-text="highUrgencyCount"></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Low / Sensory</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight font-mono"
                           :class="lowUrgencyCount > 0 ? 'text-amber-600' : 'text-slate-900'"
                           x-text="lowUrgencyCount"></p>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN (Vertical Stack of 3 Cards, Matching Attachment) --}}
        <div class="lg:col-span-5 xl:col-span-4 flex flex-col gap-3.5">

            {{-- Stacked Card 1: Registered Children (matching "+8.50% from last month") --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex items-center justify-between flex-1">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Registered Children</p>
                    <p class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-mono mt-1" x-text="enrolledChildren"></p>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
                      :class="monthlyStats.children.is_positive ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'">
                    <svg x-show="monthlyStats.children.is_positive" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <svg x-show="!monthlyStats.children.is_positive" x-cloak class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                    <span x-text="monthlyStats.children.formatted"></span>
                </span>
            </div>

            {{-- Stacked Card 2: Units Responding (matching "+2.10% from last month") --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex items-center justify-between flex-1">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Units Responding</p>
                    <p class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-mono mt-1" x-text="respondingCount"></p>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
                      :class="monthlyStats.units.is_positive ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'">
                    <svg x-show="monthlyStats.units.is_positive" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <svg x-show="!monthlyStats.units.is_positive" x-cloak class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                    <span x-text="monthlyStats.units.formatted"></span>
                </span>
            </div>

            {{-- Stacked Card 3: Resolved Today (matching "+14.20% from last month") --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm flex items-center justify-between flex-1">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Resolved Today</p>
                    <p class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 font-mono mt-1" x-text="resolvedToday"></p>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold whitespace-nowrap"
                      :class="monthlyStats.resolved.is_positive ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-rose-50 border border-rose-200 text-rose-700'">
                    <svg x-show="monthlyStats.resolved.is_positive" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                    <svg x-show="!monthlyStats.resolved.is_positive" x-cloak class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                    <span x-text="monthlyStats.resolved.formatted"></span>
                </span>
            </div>
        </div>
    </div>

    {{-- MIDDLE ROW: Live Emergency Map (Light Mode Standard OpenStreetMap) --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Live Emergency Map — Parañaque City</h3>
                <p class="text-xs text-slate-500">Real-time incident locations across Parañaque City</p>
            </div>

            {{-- Filter Pills & Status Indicators --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <div class="inline-flex items-center rounded-xl bg-slate-100 border border-slate-200 p-1 text-xs">
                    <button type="button" @click="mapFilter = 'active'; hideResolvedMap = true; syncMarkers()"
                            class="px-2.5 py-1 rounded-lg font-medium transition"
                            :class="mapFilter === 'active' ? 'bg-white text-brand-800 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">Active Only</button>
                    <button type="button" @click="mapFilter = 'high'; hideResolvedMap = true; syncMarkers()"
                            class="px-2.5 py-1 rounded-lg font-medium transition"
                            :class="mapFilter === 'high' ? 'bg-white text-critical-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">Critical</button>
                    <button type="button" @click="mapFilter = 'responding'; hideResolvedMap = true; syncMarkers()"
                            class="px-2.5 py-1 rounded-lg font-medium transition"
                            :class="mapFilter === 'responding' ? 'bg-white text-amber-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">Responding</button>
                    <button type="button" @click="mapFilter = 'resolved'; hideResolvedMap = false; syncMarkers()"
                            class="px-2.5 py-1 rounded-lg font-medium transition"
                            :class="mapFilter === 'resolved' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">Resolved</button>
                    <button type="button" @click="mapFilter = 'all'; syncMarkers()"
                            class="px-2.5 py-1 rounded-lg font-medium transition"
                            :class="mapFilter === 'all' ? 'bg-white text-slate-900 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">All</button>
                </div>

                {{-- Hide Resolved Pins Quick Checkbox Toggle --}}
                <label class="inline-flex items-center gap-1.5 cursor-pointer text-xs font-medium text-slate-600 select-none bg-slate-50 border border-slate-200 px-2.5 py-1.5 rounded-xl hover:bg-slate-100 transition shadow-sm">
                    <input type="checkbox" x-model="hideResolvedMap" @change="if (!hideResolvedMap && mapFilter === 'active') { mapFilter = 'all'; } syncMarkers();" class="rounded border-slate-300 text-brand-700 focus:ring-brand-700">
                    <span>Hide Resolved</span>
                </label>

                {{-- Status indicators --}}
                <div class="hidden lg:flex items-center gap-2.5 text-xs font-medium text-slate-600 pl-1">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-critical-500 animate-pulse"></span> Critical
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span> Responding
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Resolved
                    </span>
                </div>
            </div>
        </div>

        {{-- Leaflet Map Container --}}
        <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
            <div 
                id="emergency-map" 
                style="height: 400px; min-height: 400px; width: 100%;"
                class="relative h-[400px] w-full z-0" 
                wire:ignore 
                x-init="
                    const initMapWhenReady = () => {
                        if (typeof L === 'undefined') {
                            setTimeout(initMapWhenReady, 100);
                            return;
                        }

                        if ($el._leaflet_id) {
                            return;
                        }

                        const paranaqueCenter = [14.4793, 121.0198];
                        const map = L.map($el, {
                            zoomControl: true,
                            scrollWheelZoom: true
                        }).setView(paranaqueCenter, 13);

                        // Standard OpenStreetMap Light Basemap (No API key needed, zero watermark, clean light tiles)
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href=\'https://www.openstreetmap.org/copyright\' target=\'_blank\'>OpenStreetMap</a> contributors',
                            maxZoom: 19
                        }).addTo(map);

                        setTimeout(() => map.invalidateSize(), 150);
                        setTimeout(() => map.invalidateSize(), 400);

                        $watch('currentTab', tab => {
                            if (tab === 'dashboard') {
                                setTimeout(() => {
                                    map.invalidateSize();
                                    syncMarkers();
                                }, 150);
                            }
                        });

                        $watch('sidebarCollapsed', () => {
                            setTimeout(() => {
                                if (map) map.invalidateSize();
                            }, 220);
                        });

                        const markers = {};

                        function getMarkerColor(status, urgency) {
                            if (status === 'resolved') return '#10B981';
                            if (status === 'cancelled') return '#94A3B8';
                            if (urgency === 'high') return '#D9534F';
                            return '#F59E0B';
                        }

                        function buildPopupHtml(e) {
                            let statusBadge;
                            if (e.status === 'resolved') {
                                statusBadge = `<span style='background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Resolved</span>`;
                            } else if (e.status === 'responding') {
                                statusBadge = `<span style='background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Responding</span>`;
                            } else if (e.status === 'cancelled') {
                                statusBadge = `<span style='background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Cancelled (False Alarm)</span>`;
                            } else {
                                statusBadge = `<span style='background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Pending</span>`;
                            }
                            
                            const urgencyBadge = e.urgency === 'high'
                                ? `<span style='background: #fee2e2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 10px;'>High Urgency</span>`
                                : `<span style='background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 10px;'>Low Urgency</span>`;

                            const childName = (e.child && e.child.name) ? e.child.name : 'Unknown Child';
                            const childCondition = (e.child && e.child.condition) ? e.child.condition : 'Condition not specified';
                            const locationText = e.location || 'Parañaque City';
                            const notesHtml = e.guardianNotes ? `<div style='font-size: 11px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 5px 8px; color: #475569; margin-top: 6px;'><b>Notes:</b> ${e.guardianNotes}</div>` : '';
                            const coordsHtml = e.coords ? `<div style='font-size: 10px; font-family: monospace; color: #64748b; margin-top: 3px;'>${e.coords}</div>` : '';

                            return `
                                <div style='font-family: inherit; min-width: 210px; padding: 2px 0;'>
                                    <div style='display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;'>
                                        ${urgencyBadge}
                                        ${statusBadge}
                                    </div>
                                    <div style='font-size: 14px; font-weight: 700; color: #0f172a; line-height: 1.2;'>${childName}</div>
                                    <div style='font-size: 11px; font-weight: 600; color: #005B96; margin-top: 2px;'>${childCondition}</div>
                                    <div style='font-size: 11px; color: #475569; margin-top: 6px;'>
                                        <span>📍</span> <b>${locationText}</b>
                                    </div>
                                    ${coordsHtml}
                                    ${notesHtml}
                                </div>
                            `;
                        }

                        function getFilteredList(forceEmergency = null) {
                            const rawList = (typeof $data !== 'undefined' && Array.isArray($data.emergencies))
                                ? $data.emergencies
                                : (Array.isArray(emergencies) ? emergencies : []);
                            const currentFilter = (typeof $data !== 'undefined' && $data.mapFilter)
                                ? $data.mapFilter
                                : 'active';
                            const hideResolved = (typeof $data !== 'undefined') ? Boolean($data.hideResolvedMap) : false;

                            const filtered = rawList.filter(e => {
                                if (forceEmergency && e.id === forceEmergency.id) {
                                    return true;
                                }

                                const isResolved = e.status === 'resolved';
                                const isCancelled = e.status === 'cancelled';
                                const isActive = !isResolved && !isCancelled;

                                // If hideResolved is checked, resolved emergencies are hidden unless explicitly viewing 'resolved' filter
                                if (hideResolved && isResolved && currentFilter !== 'resolved') {
                                    return false;
                                }

                                // Cancelled emergencies (false alarms) are NEVER active and are removed from the map
                                if (isCancelled && (currentFilter !== 'all' || hideResolved)) {
                                    return false;
                                }

                                if (currentFilter === 'active') {
                                    return isActive;
                                }
                                if (currentFilter === 'high') {
                                    return isActive && e.urgency === 'high';
                                }
                                if (currentFilter === 'responding') {
                                    return e.status === 'responding';
                                }
                                if (currentFilter === 'resolved') {
                                    return isResolved;
                                }
                                if (currentFilter === 'all') {
                                    return true;
                                }
                                return true;
                            });

                            if (forceEmergency && !filtered.some(item => item.id === forceEmergency.id)) {
                                filtered.push(forceEmergency);
                            }

                            return filtered;
                        }

                        function syncMarkers(forceEmergency = null) {
                            const list = getFilteredList(forceEmergency);
                            const currentIds = new Set();
                            
                            list.forEach(e => {
                                if (e.lat === null || e.lng === null || e.lat === undefined || e.lng === undefined) return;
                                const lat = parseFloat(e.lat);
                                const lng = parseFloat(e.lng);
                                if (isNaN(lat) || isNaN(lng)) return;

                                currentIds.add(e.id);
                                const color = getMarkerColor(e.status, e.urgency);
                                const popupContent = buildPopupHtml(e);
                                
                                const isResolved = e.status === 'resolved';
                                const isCancelled = e.status === 'cancelled';
                                const isFocused = forceEmergency && forceEmergency.id === e.id;

                                const radius = isFocused ? 14 : ((e.urgency === 'high' && !isResolved && !isCancelled) ? 10 : 8);
                                const fillOpacity = isFocused ? 1.0 : ((isResolved || isCancelled) ? 0.75 : 0.95);
                                const weight = isFocused ? 3.5 : 2.5;

                                if (markers[e.id]) {
                                    markers[e.id].setLatLng([lat, lng]);
                                    markers[e.id].setStyle({ 
                                       fillColor: color,
                                       radius: radius,
                                       fillOpacity: fillOpacity,
                                       weight: weight
                                    });
                                    markers[e.id].setPopupContent(popupContent);
                                    if (!map.hasLayer(markers[e.id])) {
                                        markers[e.id].addTo(map);
                                    }
                                } else {
                                    markers[e.id] = L.circleMarker([lat, lng], { 
                                        radius: radius, 
                                        color: '#ffffff', 
                                        weight: weight, 
                                        fillColor: color, 
                                        fillOpacity: fillOpacity 
                                    })
                                    .bindPopup(popupContent)
                                    .addTo(map);
                                }
                            });

                            // Remove markers that are no longer in the filtered list (except forced emergency)
                            for (const id in markers) {
                                if (!currentIds.has(Number(id)) && !currentIds.has(String(id))) {
                                    map.removeLayer(markers[id]);
                                    delete markers[id];
                                }
                            }
                        }
                        
                        window.syncOverviewMapMarkers = syncMarkers;
                        window.focusMapOnEmergency = (e) => {
                            if (!e || e.lat === null || e.lng === null || e.lat === undefined || e.lng === undefined) return;
                            const lat = parseFloat(e.lat);
                            const lng = parseFloat(e.lng);
                            if (isNaN(lat) || isNaN(lng)) return;

                            // Unhide resolved pins on the filter pills if this emergency is resolved
                            if (typeof $data !== 'undefined') {
                                if (e.status === 'resolved' || e.status === 'cancelled') {
                                    $data.hideResolvedMap = false;
                                    $data.mapFilter = 'all';
                                }
                            }

                            map.invalidateSize();
                            syncMarkers(e);

                            // Guarantee marker exists and is attached to the map
                            if (!markers[e.id]) {
                                const color = getMarkerColor(e.status, e.urgency);
                                const popupContent = buildPopupHtml(e);
                                markers[e.id] = L.circleMarker([lat, lng], { 
                                    radius: 14, 
                                    color: '#ffffff', 
                                    weight: 3.5, 
                                    fillColor: color, 
                                    fillOpacity: 1.0 
                                })
                                .bindPopup(popupContent)
                                .addTo(map);
                            } else {
                                if (!map.hasLayer(markers[e.id])) {
                                    markers[e.id].addTo(map);
                                }
                                markers[e.id].setStyle({
                                    radius: 14,
                                    weight: 3.5,
                                    fillOpacity: 1.0
                                });
                            }

                            // Bring marker to front and fly to coordinates
                            markers[e.id].bringToFront();
                            map.flyTo([lat, lng], 17, { duration: 0.6 });

                            setTimeout(() => {
                                if (markers[e.id]) {
                                    if (!map.hasLayer(markers[e.id])) {
                                        markers[e.id].addTo(map);
                                    }
                                    markers[e.id].openPopup();
                                }
                            }, 350);
                        };
                        syncMarkers();
                        $watch('emergencies', () => syncMarkers());
                        $watch('mapFilter', () => syncMarkers());
                        $watch('hideResolvedMap', () => syncMarkers());
                        window.addEventListener('emergencies-updated', () => syncMarkers());
                    };

                    initMapWhenReady();
                "
            ></div>

            {{-- Standby Banner overlay when empty --}}
            <div x-show="alertsCount === 0" x-cloak
                 class="absolute bottom-4 left-4 z-[500] flex items-center gap-2 rounded-xl bg-white/95 border border-slate-200 px-3.5 py-2 text-xs text-slate-700 shadow-md backdrop-blur">
                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Standby Mode · 0 active emergencies in Parañaque City</span>
            </div>
        </div>
    </div>

    {{-- ANALYTICS ROW: Incident Breakdown & Monthly Trends (Table 1.14) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        {{-- Incident Category Breakdown --}}
        <div class="lg:col-span-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Incident Classification Breakdown</h3>
                        <p class="text-xs text-slate-500">Distribution of CSN emergency triggers by category</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 font-mono" x-text="emergencies.length + ' Total'"></span>
                </div>
                <div class="space-y-3.5">
                    <template x-for="item in incidentBreakdown" :key="item.key">
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-semibold text-slate-700" x-text="item.label"></span>
                                <div class="flex items-center gap-2 font-mono">
                                    <span class="font-bold text-slate-900" x-text="item.count"></span>
                                    <span class="text-slate-400" x-text="'(' + item.pct + '%)'"></span>
                                </div>
                            </div>
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full transition-all duration-500"
                                     :class="item.color"
                                     :style="`width: ${Math.max(item.count > 0 ? 6 : 0, item.pct)}%`"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Primary Protocol: <strong class="text-slate-700">Sensory-Safe &amp; Rapid Dispatch</strong></span>
                <button type="button" @click="currentTab = 'reports'" class="font-semibold text-brand-700 hover:text-brand-800">View Full Reports &rarr;</button>
            </div>
        </div>

        {{-- Monthly Emergency Trends Bar Chart (Table 1.14) --}}
        <div class="lg:col-span-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Monthly Emergency Trends</h3>
                        <p class="text-xs text-slate-500">6-month alert volume &amp; resolution rate across Parañaque</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span>{{ number_format($monthlyTrends['resolution_rate'] ?? 100.0, 1) }}% Resolved</span>
                    </span>
                </div>

                <div class="grid grid-cols-6 gap-3 items-end h-40 pt-4 px-2 border-b border-slate-100">
                    @foreach(($monthlyTrends['months'] ?? []) as $tm)
                        <div class="flex flex-col items-center gap-1.5 h-full justify-end group">
                            <span class="text-[10px] font-mono font-semibold {{ $tm['is_current'] ? 'font-bold text-brand-700' : 'text-slate-400 group-hover:text-slate-700' }} transition">
                                @if($tm['is_current'])
                                    <span x-text="emergencies.length"></span>
                                @else
                                    {{ $tm['count'] }}
                                @endif
                            </span>
                            <div class="w-full max-w-[34px] rounded-t-lg transition-all {{ $tm['is_current'] ? 'bg-brand-700 shadow-sm' : 'bg-brand-100 group-hover:bg-brand-700' }}"
                                 @if($tm['is_current'])
                                     :style="`height: ${Math.min(100, Math.max(12, emergencies.length > 0 ? (emergencies.length / Math.max(1, {{ max(1, max(array_column($monthlyTrends['months'] ?? [], 'count') ?: [1])) }})) * 100 : 8))}%`"
                                 @else
                                     style="height: {{ max(8, $tm['pct']) }}%"
                                 @endif
                            ></div>
                            <span class="text-[11px] {{ $tm['is_current'] ? 'font-bold text-brand-800' : 'font-medium text-slate-500' }} pb-1">
                                {{ $tm['is_current'] ? 'Current' : $tm['name'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                <span>Avg. PDRRMO Dispatch Time: <strong class="text-slate-800 font-mono">{{ $monthlyTrends['avg_dispatch_time'] ?? '3m 45s' }}</strong></span>
                <span class="text-slate-400">Live 24/7 Emergency Dispatch</span>
            </div>
        </div>
    </div>

    {{-- BOTTOM ROW: Recent Emergencies Table (Light Mode) --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Recent Emergencies &amp; Alerts</h3>
                <p class="text-xs text-slate-500">Chronological incident log across all Parañaque zones</p>
            </div>
            <span class="text-xs text-slate-500 font-mono" x-text="emergencies.length + ' Total Logs'"></span>
        </div>

        {{-- Table / List --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold uppercase tracking-wider text-slate-400">
                        <th class="pb-3 pl-2">Child &amp; Condition</th>
                        <th class="pb-3">Type</th>
                        <th class="pb-3">Location</th>
                        <th class="pb-3">Reported / Elapsed</th>
                        <th class="pb-3">Status</th>
                        <th class="pb-3 text-right pr-2">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="e in emergencies.slice(0, 6)" :key="e.id">
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 pl-2">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 border border-slate-200 font-bold text-xs text-slate-700"
                                         x-text="e.child ? e.child.avatarInitials : '??'"></div>
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm" x-text="e.child ? e.child.name : 'Unknown Child'"></p>
                                        <p class="text-xs text-slate-500" x-text="e.child ? e.child.condition : 'No condition noted'"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 text-xs font-medium text-slate-700" x-text="e.type"></td>
                            <td class="py-3.5 text-xs text-slate-500" x-text="e.location"></td>
                            <td class="py-3.5 text-xs text-slate-500">
                                <span x-text="e.reportedAt"></span>
                                <span x-show="e.status === 'pending' || e.status === 'responding'"
                                      class="ml-1.5 inline-flex items-center rounded bg-rose-50 px-1.5 py-0.5 font-mono text-[11px] font-bold text-rose-700 border border-rose-200"
                                      x-text="getElapsedTimer(e)"></span>
                            </td>
                            <td class="py-3.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                      :class="{
                                          'bg-critical-50 text-critical-700 border border-critical-200': e.status === 'pending',
                                          'bg-amber-50 text-amber-700 border border-amber-200': e.status === 'responding',
                                          'bg-emerald-50 text-emerald-700 border border-emerald-200': e.status === 'resolved',
                                          'bg-slate-100 text-slate-600 border border-slate-200': e.status === 'cancelled'
                                      }"
                                      x-text="e.status === 'responding' && e.responder ? 'On the Way' : e.status"></span>
                            </td>
                            <td class="py-3.5 text-right pr-2 whitespace-nowrap">
                                <button x-show="e.lat !== null && e.lng !== null"
                                        type="button"
                                        @click="focusEmergencyOnMap(e)"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-brand-700 hover:bg-brand-50 transition">
                                    <span>📍 Locate</span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>

            {{-- Empty State (Shown when no emergencies exist) --}}
            <div x-show="emergencies.length === 0" x-cloak class="py-12 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 border border-slate-200 text-emerald-600 mb-3 shadow-inner">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-800">No Emergency Incidents Reported</p>
                <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">All registered CSN children and barangay zones in Parañaque City are standing by securely.</p>
            </div>
        </div>
    </div>
</div>
