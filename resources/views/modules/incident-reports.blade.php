{{-- ============ MODULE 5: INCIDENT REPORTING & EXPORTING ============ --}}
<div class="space-y-5" x-data="{ exportMsg: '' }">

    {{-- Export actions --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-base font-semibold text-slate-800">Historical Incidents</h2>
        <div class="flex gap-2">
            <button type="button"
                    @click="exportMsg = 'PDF report generated successfully.'; setTimeout(() => exportMsg = '', 3000)"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-ring">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg>
                Export PDF Report
            </button>
            <button type="button"
                    @click="exportMsg = 'CSV data exported successfully.'; setTimeout(() => exportMsg = '', 3000)"
                    class="inline-flex min-h-[44px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus-ring">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                Export CSV Data
            </button>
        </div>
    </div>

    {{-- Success feedback --}}
    <div x-show="exportMsg" x-cloak x-transition
         class="flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
        <span x-text="exportMsg"></span>
    </div>

    {{-- Incident table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Incident ID</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Child</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Resolution Time</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Responder Unit</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="inc in incidents" :key="inc.id">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="inc.id"></td>
                            <td class="px-4 py-3 font-medium text-slate-700" x-text="inc.child"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="inc.type"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="inc.resolvedIn"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="inc.unit"></td>
                            <td class="px-4 py-3 text-slate-500" x-text="inc.date"></td>
                        </tr>
                    </template>
                    <tr x-show="incidents.length === 0" x-cloak>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No incidents logged yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
