{{-- ============ MODULE 1: DASHBOARD OVERVIEW ============ --}}
<div class="space-y-6">

    {{-- Metric cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Total Active Emergencies</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-critical-500/10 text-critical-500">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold text-critical-600" x-text="alertsCount"></p>
            <p class="mt-1 text-xs text-slate-400">Requires immediate attention</p>
        </div>

        <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Responding / On The Way</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-warning-500/10 text-warning-500">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h13l-4-4m4 4l-4 4"/><path d="M16 6h3a2 2 0 012 2v8a2 2 0 01-2 2h-3"/></svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold text-amber-600" x-text="respondingCount"></p>
            <p class="mt-1 text-xs text-slate-400">Units dispatched</p>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Resolved Today</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-success-600/10 text-success-600">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold text-emerald-600" x-text="resolvedToday"></p>
            <p class="mt-1 text-xs text-slate-400">Closed within SLA</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-slate-500">Enrolled Children</p>
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700/10 text-brand-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M4 21v-1a7 7 0 0114 0v1"/></svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-bold text-brand-800" x-text="enrolledChildren"></p>
            <p class="mt-1 text-xs text-slate-400">Active CSN profiles</p>
        </div>
    </div>

    {{-- Map + activity log --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Live Emergency Map — Parañaque City</h2>
                    <p class="text-xs text-slate-400">OpenStreetMap / Leaflet GIS telemetry</p>
                </div>
                
                {{-- Legend --}}
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                        <span class="h-2.5 w-2.5 rounded-full bg-critical-500 animate-pulse"></span>
                        High Urgency
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                        Responding / Low
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-medium text-slate-600">
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        Resolved
                    </span>
                </div>
            </div>

            {{-- Leaflet Map Container --}}
            <div 
                id="emergency-map" 
                style="height: 420px; min-height: 420px; width: 100%;"
                class="relative h-[420px] w-full -mx-5 -mb-5 mt-3 overflow-hidden rounded-b-xl z-0 border-t border-slate-100" 
                wire:ignore 
                x-init="
                    const initMapWhenReady = () => {
                        if (typeof L === 'undefined') {
                            setTimeout(initMapWhenReady, 100);
                            return;
                        }

                        const paranaqueCenter = [14.4793, 121.0198];
                        const map = L.map($el, {
                            zoomControl: true,
                            scrollWheelZoom: true
                        }).setView(paranaqueCenter, 13);

                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; <a href=\'https://www.openstreetmap.org/copyright\' target=\'_blank\'>OpenStreetMap</a> contributors',
                            maxZoom: 19
                        }).addTo(map);

                        setTimeout(() => map.invalidateSize(), 150);
                        setTimeout(() => map.invalidateSize(), 400);

                        $watch('currentTab', tab => {
                            if (tab === 'dashboard') {
                                setTimeout(() => map.invalidateSize(), 150);
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
                                ? `<span style='background: #ecfdf5; color: #047857; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Resolved</span>`
                                : (e.status === 'responding'
                                    ? `<span style='background: #f0f9ff; color: #0369a1; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Responding</span>`
                                    : `<span style='background: #fef2f2; color: #b91c1c; padding: 2px 7px; border-radius: 9999px; font-weight: 600; font-size: 10px; text-transform: uppercase;'>Pending</span>`);
                            
                            const urgencyBadge = e.urgency === 'high'
                                ? `<span style='background: #fef2f2; color: #dc2626; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 10px;'>High Urgency</span>`
                                : `<span style='background: #fffbeb; color: #d97706; padding: 2px 6px; border-radius: 4px; font-weight: 600; font-size: 10px;'>Low Urgency</span>`;

                            const childName = (e.child && e.child.name) ? e.child.name : 'Unknown Child';
                            const childCondition = (e.child && e.child.condition) ? e.child.condition : 'Condition not specified';
                            const locationText = e.location || 'Parañaque City';
                            const notesHtml = e.guardianNotes ? `<div style='font-size: 11px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 5px 8px; color: #92400e; margin-top: 6px;'><b>Notes:</b> ${e.guardianNotes}</div>` : '';
                            const coordsHtml = e.coords ? `<div style='font-size: 10px; font-family: monospace; color: #94a3b8; margin-top: 3px;'>${e.coords}</div>` : '';

                            return `
                                <div style='font-family: inherit; min-width: 210px; padding: 2px 0;'>
                                    <div style='display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px;'>
                                        ${urgencyBadge}
                                        ${statusBadge}
                                    </div>
                                    <div style='font-size: 14px; font-weight: 700; color: #1e293b; line-height: 1.2;'>${childName}</div>
                                    <div style='font-size: 11px; font-weight: 600; color: #005B96; margin-top: 2px;'>${childCondition}</div>
                                    <div style='font-size: 11px; color: #475569; margin-top: 6px;'>
                                        <span>📍</span> <b>${locationText}</b>
                                    </div>
                                    ${coordsHtml}
                                    ${notesHtml}
                                </div>
                            `;
                        }

                        function syncMarkers() {
                            if (!Array.isArray(emergencies)) return;

                            const currentIds = new Set();
                            
                            emergencies.forEach(e => {
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
                                        weight: 2,
                                        fillColor: color, 
                                        fillOpacity: 0.95 
                                    })
                                    .bindPopup(popupContent)
                                    .addTo(map);
                                }
                            });

                            // Remove markers that are no longer in emergencies
                            for (const id in markers) {
                                if (!currentIds.has(Number(id)) && !currentIds.has(String(id))) {
                                    map.removeLayer(markers[id]);
                                    delete markers[id];
                                }
                            }
                        }
                        
                        syncMarkers();
                        $watch('emergencies', () => syncMarkers());
                    };

                    initMapWhenReady();
                "
            ></div>
        </div>

        {{-- Live activity log --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-800">Live Activity Log</h2>
            <p x-show="activityLog.length === 0" x-cloak class="text-sm text-slate-400">No recent activity.</p>
            <ol class="space-y-4">
                <template x-for="(log, i) in activityLog" :key="i">
                    <li class="flex gap-3">
                        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
                              :class="{
                                  'bg-critical-500': log.tone === 'critical',
                                  'bg-amber-500': log.tone === 'warning',
                                  'bg-emerald-500': log.tone === 'success'
                              }"></span>
                        <div class="min-w-0">
                            <p class="text-sm text-slate-700" x-text="log.text"></p>
                            <p class="text-xs text-slate-400" x-text="log.time"></p>
                        </div>
                    </li>
                </template>
            </ol>
        </div>
    </div>
</div>
