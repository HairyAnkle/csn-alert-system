<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

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
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-slate-800">Live Emergency Map — Parañaque City</h2>
                <span class="text-xs text-slate-400">Leaflet GIS feed</span>
            </div>

            {{-- Changed w-full and adjusted padding or margins if needed --}}
            <div 
                id="emergency-map" 
                class="relative h-80 w-full -mx-5 -mb-5 mt-4 overflow-hidden rounded-b-xl z-0" 
                wire:ignore 
                x-init="
                    const map = L.map($el).setView([14.4793, 121.0198], 14);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);

                    setTimeout(() => {
                        map.invalidateSize();
                    }, 100);

                    const markers = {};
                    function syncMarkers() {
                        if (!Array.isArray(emergencies)) return;
                        
                        emergencies.forEach(e => {
                            if (!e.lat || !e.lng) return;
                            
                            if (!markers[e.id]) {
                                const color = e.status === 'resolved' ? '#10B981' : (e.urgency === 'high' ? '#D9534F' : '#F59E0B');
                                
                                const popupContent = `
                                    <div style='font-family: inherit;'>
                                        <b style='font-size: 13px;'>${e.child ? e.child.name : 'Unknown'}</b><br>
                                        <span style='font-size: 11px; color: #64748b;'>${e.child && e.child.condition ? e.child.condition : ''}</span><br>
                                        <span style='font-size: 11px;'>${e.location || ''}</span>
                                    </div>
                                `;

                                markers[e.id] = L.circleMarker([e.lat, e.lng], { 
                                    radius: 8, 
                                    color: '#ffffff', 
                                    weight: 2,
                                    fillColor: color, 
                                    fillOpacity: 0.9 
                                })
                                .bindPopup(popupContent)
                                .addTo(map);
                            }
                        });
                    }
                    
                    syncMarkers();
                    $watch('emergencies', () => syncMarkers());
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
