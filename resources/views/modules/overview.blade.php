{{-- ============ MODULE 1: DASHBOARD OVERVIEW (LIGHT THEME WITH CARD STRUCTURE MATCHING ATTACHMENT) ============ --}}
<div class="space-y-6">

    {{-- Top Greeting (Matching "Welcome back, Jordan 👋" in Attachment) --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 flex items-center gap-2">
                <span>Welcome back, <span x-text="currentUser.name ? currentUser.name.split(' ')[0] : 'Jordan'"></span></span>
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
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex items-center rounded-xl bg-slate-100 border border-slate-200 p-1 text-xs">
                    <button type="button" @click="emergencyFilter = 'all'"
                            class="px-3 py-1 rounded-lg font-medium transition"
                            :class="emergencyFilter === 'all' ? 'bg-white text-slate-900 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">All</button>
                    <button type="button" @click="emergencyFilter = 'high'"
                            class="px-3 py-1 rounded-lg font-medium transition"
                            :class="emergencyFilter === 'high' ? 'bg-white text-critical-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">High</button>
                    <button type="button" @click="emergencyFilter = 'responding'"
                            class="px-3 py-1 rounded-lg font-medium transition"
                            :class="emergencyFilter === 'responding' ? 'bg-white text-amber-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">Responding</button>
                    <button type="button" @click="emergencyFilter = 'resolved'"
                            class="px-3 py-1 rounded-lg font-medium transition"
                            :class="emergencyFilter === 'resolved' ? 'bg-white text-emerald-700 shadow-sm font-semibold' : 'text-slate-600 hover:text-slate-900'">Resolved</button>
                </div>

                {{-- Status indicators --}}
                <div class="hidden md:flex items-center gap-3 text-xs font-medium text-slate-600">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-critical-500 animate-pulse"></span> High
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

                        const markers = {};

                        function getMarkerColor(status, urgency) {
                            if (status === 'resolved') return '#10B981';
                            if (urgency === 'high') return '#D9534F';
                            return '#F59E0B';
                        }

                        function buildPopupHtml(e) {
                            const statusBadge = e.status === 'resolved' 
                                ? `<span style='background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Resolved</span>`
                                : (e.status === 'responding'
                                    ? `<span style='background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Responding</span>`
                                    : `<span style='background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Pending</span>`);
                            
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

                        function getFilteredList() {
                            const rawList = (typeof $data !== 'undefined' && Array.isArray($data.emergencies))
                                ? $data.emergencies
                                : (Array.isArray(emergencies) ? emergencies : []);
                            const currentFilter = (typeof $data !== 'undefined' && $data.emergencyFilter)
                                ? $data.emergencyFilter
                                : (typeof emergencyFilter !== 'undefined' ? emergencyFilter : 'all');

                            if (currentFilter === 'all') {
                                return rawList;
                            }
                            if (currentFilter === 'high') {
                                return rawList.filter(e => e.urgency === 'high' && e.status !== 'resolved');
                            }
                            if (currentFilter === 'responding') {
                                return rawList.filter(e => e.status === 'responding');
                            }
                            if (currentFilter === 'resolved') {
                                return rawList.filter(e => e.status === 'resolved');
                            }
                            return rawList;
                        }

                        function syncMarkers() {
                            const list = getFilteredList();
                            const currentIds = new Set();
                            
                            list.forEach(e => {
                                if (e.lat === null || e.lng === null || e.lat === undefined || e.lng === undefined) return;
                                const lat = parseFloat(e.lat);
                                const lng = parseFloat(e.lng);
                                if (isNaN(lat) || isNaN(lng)) return;

                                currentIds.add(e.id);
                                const color = getMarkerColor(e.status, e.urgency);
                                const popupContent = buildPopupHtml(e);
                                const radius = (e.urgency === 'high' && e.status !== 'resolved') ? 9 : 7;

                                if (markers[e.id]) {
                                    markers[e.id].setLatLng([lat, lng]);
                                    markers[e.id].setStyle({ 
                                       fillColor: color,
                                       radius: radius 
                                    });
                                    markers[e.id].setPopupContent(popupContent);
                                } else {
                                    markers[e.id] = L.circleMarker([lat, lng], { 
                                        radius: radius, 
                                        color: '#ffffff', 
                                        weight: 2.5, 
                                        fillColor: color, 
                                        fillOpacity: 0.95 
                                    })
                                    .bindPopup(popupContent)
                                    .addTo(map);
                                }
                            });

                            // Remove markers that are no longer in the filtered list
                            for (const id in markers) {
                                if (!currentIds.has(Number(id)) && !currentIds.has(String(id))) {
                                    map.removeLayer(markers[id]);
                                    delete markers[id];
                                }
                            }
                        }
                        
                        syncMarkers();
                        $watch('emergencies', () => syncMarkers());
                        $watch('emergencyFilter', () => syncMarkers());
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
                        <th class="pb-3">Reported</th>
                        <th class="pb-3 text-right pr-2">Status</th>
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
                            <td class="py-3.5 text-xs text-slate-500" x-text="e.reportedAt"></td>
                            <td class="py-3.5 text-right pr-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                      :class="{
                                          'bg-critical-50 text-critical-700 border border-critical-200': e.status === 'pending',
                                          'bg-amber-50 text-amber-700 border border-amber-200': e.status === 'responding',
                                          'bg-emerald-50 text-emerald-700 border border-emerald-200': e.status === 'resolved'
                                      }" x-text="e.status"></span>
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
