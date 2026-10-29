{{-- ============ MODULE 5: INCIDENT REPORTING & EXPORTING ============ --}}
<div class="space-y-5">

    {{-- Export actions & title --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-bold text-slate-800">Historical Incidents & Reports</h2>
            <p class="text-xs text-slate-500">Official post-incident resolution logs, after-action summaries, and export tools.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button"
                    @click="currentTab = 'emergency'; activeModal = 'logEmergency'"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-lg bg-brand-700 px-3.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-800 focus-ring">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Create New Report
            </button>
            <button type="button"
                    @click="triggerExport('pdf')"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-400 focus-ring">
                <svg class="h-4 w-4 text-rose-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                Export PDF Report
            </button>
            <button type="button"
                    @click="triggerExport('csv')"
                    class="inline-flex min-h-[42px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-400 focus-ring">
                <svg class="h-4 w-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export CSV Data
            </button>
        </div>
    </div>

    {{-- Export feedback toast --}}
    <div x-show="exportToast" x-cloak x-transition
         class="flex items-center gap-2.5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200 shadow-sm">
        <svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span x-text="exportToast"></span>
    </div>

    {{-- Filter bar: Date range, Presets, Type, and Search --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Start Date --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Start Date</label>
                <input type="date"
                       x-model="incidentStartDate"
                       @change="fetchIncidents()"
                       class="min-h-[40px] w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700 focus-ring">
            </div>

            {{-- End Date --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">End Date</label>
                <input type="date"
                       x-model="incidentEndDate"
                       @change="fetchIncidents()"
                       class="min-h-[40px] w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700 focus-ring">
            </div>

            {{-- Incident Type --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Emergency Type</label>
                <select x-model="incidentType"
                        @change="fetchIncidents()"
                        class="min-h-[40px] w-full rounded-lg border border-slate-300 bg-white px-3 text-xs text-slate-700 focus-ring">
                    <option value="all">All Incident Types</option>
                    <option value="medical_crisis">Medical Crisis</option>
                    <option value="seizure_sensory">Seizure / Sensory Overload</option>
                    <option value="wandering">Wandering / Elopement</option>
                    <option value="fire">Fire / Environmental</option>
                </select>
            </div>

            {{-- Keyword Search --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">Keyword Search</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-slate-400">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                    </div>
                    <input type="text"
                           x-model="incidentSearch"
                           @input.debounce.350ms="fetchIncidents()"
                           placeholder="Child, unit, notes..."
                           class="min-h-[40px] w-full rounded-lg border border-slate-300 bg-white pl-8 pr-3 text-xs text-slate-700 placeholder-slate-400 focus-ring">
                </div>
            </div>
        </div>

        {{-- Preset filter pills & Reset --}}
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-slate-400 font-medium mr-1">Quick Range:</span>
                <button type="button" @click="setIncidentPreset('today')"
                        class="rounded-full px-2.5 py-1 font-medium ring-1 transition hover:bg-slate-100"
                        :class="incidentStartDate && incidentStartDate === incidentEndDate ? 'bg-brand-50 text-brand-700 ring-brand-300' : 'bg-slate-50 text-slate-600 ring-slate-200'">
                    Today
                </button>
                <button type="button" @click="setIncidentPreset('this_month')"
                        class="rounded-full px-2.5 py-1 font-medium ring-1 transition hover:bg-slate-100"
                        :class="!incidentStartDate ? 'bg-slate-50 text-slate-600 ring-slate-200' : 'bg-brand-50 text-brand-700 ring-brand-300'">
                    This Month
                </button>
                <button type="button" @click="setIncidentPreset('last_30')"
                        class="rounded-full px-2.5 py-1 font-medium ring-1 transition hover:bg-slate-100 bg-slate-50 text-slate-600 ring-slate-200">
                    Last 30 Days
                </button>
                <button type="button" @click="setIncidentPreset('all')"
                        class="rounded-full px-2.5 py-1 font-medium ring-1 transition hover:bg-slate-100"
                        :class="!incidentStartDate && !incidentEndDate ? 'bg-brand-50 text-brand-700 ring-brand-300' : 'bg-slate-50 text-slate-600 ring-slate-200'">
                    All Time
                </button>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-slate-400 font-medium" x-text="`${incidents.length} incident(s) loaded`"></span>
                <button type="button"
                        x-show="incidentStartDate || incidentEndDate || incidentType !== 'all' || incidentSearch"
                        @click="incidentStartDate = ''; incidentEndDate = ''; incidentType = 'all'; incidentSearch = ''; fetchIncidents()"
                        class="text-xs font-medium text-rose-600 hover:text-rose-700 underline">
                    Clear Filters
                </button>
            </div>
        </div>
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
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Date Resolved</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Summary Logs</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="inc in incidents" :key="inc.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            {{-- Incident ID --}}
                            <td class="px-4 py-3.5 font-mono text-xs font-bold text-brand-700" x-text="inc.id"></td>

                            {{-- Child --}}
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-slate-800" x-text="inc.child"></p>
                                <p class="text-xs text-slate-400" x-text="inc.child_condition || ''"></p>
                            </td>

                            {{-- Type --}}
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-semibold capitalize"
                                      :class="{
                                          'bg-rose-50 text-rose-700 ring-1 ring-rose-200': inc.raw_type === 'medical_crisis',
                                          'bg-amber-50 text-amber-700 ring-1 ring-amber-200': inc.raw_type === 'seizure_sensory',
                                          'bg-sky-50 text-sky-700 ring-1 ring-sky-200': inc.raw_type === 'wandering',
                                          'bg-orange-50 text-orange-700 ring-1 ring-orange-200': inc.raw_type === 'fire'
                                      }"
                                      x-text="inc.type"></span>
                            </td>

                            {{-- Resolution Time --}}
                            <td class="px-4 py-3.5 font-medium text-slate-700" x-text="inc.resolvedIn"></td>

                            {{-- Responder Unit --}}
                            <td class="px-4 py-3.5 text-xs text-slate-600" x-text="inc.unit"></td>

                            {{-- Resolved Date --}}
                            <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap" x-text="inc.date"></td>

                            {{-- Summary Logs --}}
                            <td class="px-4 py-3.5 max-w-xs">
                                <template x-if="inc.raw_summary">
                                    <p class="text-xs text-slate-700 line-clamp-2" :title="inc.raw_summary" x-text="inc.raw_summary"></p>
                                </template>
                                <template x-if="!inc.raw_summary">
                                    <span class="text-xs italic text-slate-400">No notes filed</span>
                                </template>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3.5 text-right whitespace-nowrap">
                                <button type="button" @click="openEditSummary(inc)"
                                        class="inline-flex min-h-[34px] items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 focus-ring">
                                    <svg class="h-3 w-3 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                    <span x-text="inc.raw_summary ? 'Edit Note' : 'Add Note'"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="incidents.length === 0" x-cloak>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-slate-400">
                            No resolved incidents match your selected filters.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ EDIT INCIDENT SUMMARY MODAL ============ --}}
    <div x-show="activeModal === 'editSummary' && editingSummaryIncident !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" x-transition.opacity @click="activeModal = null; editingSummaryIncident = null"></div>
        <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl" x-transition>
            <template x-if="editingSummaryIncident">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-brand-700" x-text="editingSummaryIncident.id"></span>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700" x-text="editingSummaryIncident.type"></span>
                            </div>
                            <h2 class="text-base font-bold text-slate-800 mt-1" x-text="`Incident Summary: ${editingSummaryIncident.child}`"></h2>
                        </div>
                        <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring" @click="activeModal = null; editingSummaryIncident = null" aria-label="Close">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="py-4 space-y-3">
                        <div class="grid grid-cols-2 gap-2 rounded-lg bg-slate-50 p-2.5 text-xs text-slate-600">
                            <div>
                                <span class="text-slate-400">Resolution Time:</span>
                                <span class="font-semibold text-slate-700 ml-1" x-text="editingSummaryIncident.resolvedIn"></span>
                            </div>
                            <div>
                                <span class="text-slate-400">Unit:</span>
                                <span class="font-semibold text-slate-700 ml-1" x-text="editingSummaryIncident.unit"></span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">
                                Post-Incident Summary / After-Action Review (AAR) Notes
                            </label>
                            <textarea
                                x-model="editingSummaryIncident.incident_summary"
                                rows="5"
                                placeholder="Detail the child's vital condition upon resolution, medical aid administered, sensory decompression tools used, or turnover details to guardian..."
                                class="w-full rounded-lg border border-slate-300 p-3 text-xs text-slate-800 placeholder-slate-400 focus-ring"
                            ></textarea>
                            <p class="mt-1 text-[11px] text-slate-400">
                                These notes appear in official PDF/CSV incident exports for audits and medical follow-up.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="activeModal = null; editingSummaryIncident = null"
                                class="min-h-[42px] flex-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-600 hover:bg-slate-50 focus-ring">
                            Cancel
                        </button>
                        <button type="button" @click="saveIncidentSummary()" :disabled="isSavingSummary"
                                class="inline-flex min-h-[42px] flex-1 items-center justify-center gap-2 rounded-lg bg-brand-700 text-xs font-semibold text-white hover:bg-brand-800 focus-ring disabled:opacity-50">
                            <svg x-show="isSavingSummary" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <span x-text="isSavingSummary ? 'Saving...' : 'Save Summary Notes'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
