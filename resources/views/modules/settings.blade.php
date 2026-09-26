{{-- ============ MODULE 6: SETTINGS & SUPPORT ============ --}}
{{--
    Pass $settings and $auditLog from your controller, e.g.:
    return view('modules.settings', [
        'settings' => Setting::pluck('value', 'key'), // sound toggles, hotline numbers
        'auditLog' => AuditLog::latest()->limit(50)->get(),
    ]);
--}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2"
     x-data="{
        soundOnHigh: {{ ($settings['sound_on_high'] ?? true) ? 'true' : 'false' }},
        soundOnLow: {{ ($settings['sound_on_low'] ?? true) ? 'true' : 'false' }},
        hotlinePDRRMO: @json($settings['hotline_pdrrmo'] ?? ''),
        hotlineAmbulance: @json($settings['hotline_ambulance'] ?? ''),
        auditLog: @json($auditLog ?? [])
     }">

    {{-- Alert sound notifications --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">Alert Sound Notifications</h2>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-700">High Urgency Alerts</p>
                    <p class="text-xs text-slate-400">Play sound for critical / high-urgency emergencies</p>
                </div>
                <button type="button" role="switch" :aria-checked="soundOnHigh" @click="soundOnHigh = !soundOnHigh"
                        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus-ring"
                        :class="soundOnHigh ? 'bg-brand-700' : 'bg-slate-300'">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white transition" :class="soundOnHigh ? 'translate-x-6' : 'translate-x-1'"></span>
                </button>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-700">Low Urgency Alerts</p>
                    <p class="text-xs text-slate-400">Play sound for routine / low-urgency alerts</p>
                </div>
                <button type="button" role="switch" :aria-checked="soundOnLow" @click="soundOnLow = !soundOnLow"
                        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus-ring"
                        :class="soundOnLow ? 'bg-brand-700' : 'bg-slate-300'">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white transition" :class="soundOnLow ? 'translate-x-6' : 'translate-x-1'"></span>
                </button>
            </div>
        </div>
    </div>

    {{-- Emergency hotline configuration --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-4 text-base font-semibold text-slate-800">Emergency Hotline Configuration</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">PDRRMO Hotline</label>
                <input type="text" x-model="hotlinePDRRMO" placeholder="e.g. (02) 8xxx-xxxx" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 font-mono text-sm focus-ring">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-600">Ambulance Dispatch</label>
                <input type="text" x-model="hotlineAmbulance" placeholder="e.g. 911" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 font-mono text-sm focus-ring">
            </div>
            <button type="button" class="min-h-[44px] w-full rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 focus-ring">
                Save Configuration
            </button>
        </div>
    </div>

    {{-- ISO 27001 audit log --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-base font-semibold text-slate-800">ISO 27001 Audit Log</h2>
            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">Read-only</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Timestamp</th>
                        <th class="px-4 py-2 text-left font-semibold text-slate-500">User</th>
                        <th class="px-4 py-2 text-left font-semibold text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(log, i) in auditLog" :key="i">
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-slate-500" x-text="log.time"></td>
                            <td class="px-4 py-2 text-slate-600" x-text="log.user"></td>
                            <td class="px-4 py-2 text-slate-600" x-text="log.action"></td>
                        </tr>
                    </template>
                    <tr x-show="auditLog.length === 0" x-cloak>
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-slate-400">No audit entries yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
