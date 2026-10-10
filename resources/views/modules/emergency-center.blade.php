{{-- ============ MODULE 2: EMERGENCY CENTER ============ --}}
<div class="space-y-5">

    {{-- Top Bar: Filter controls + Log Emergency button --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter emergencies">
            @php
                $filters = [
                    ['key' => 'all', 'label' => 'All'],
                    ['key' => 'high', 'label' => 'High Urgency'],
                    ['key' => 'low', 'label' => 'Low Urgency'],
                    ['key' => 'pending', 'label' => 'Pending'],
                    ['key' => 'responding', 'label' => 'Responding'],
                    ['key' => 'resolved', 'label' => 'Resolved'],
                    ['key' => 'cancelled', 'label' => 'Cancelled'],
                ];
            @endphp
            @foreach ($filters as $f)
                <button
                    type="button"
                    @click="emergencyFilter = '{{ $f['key'] }}'"
                    class="min-h-[44px] rounded-full px-4 text-sm font-medium transition focus-ring"
                    :class="emergencyFilter === '{{ $f['key'] }}' ? 'bg-brand-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                >
                    {{ $f['label'] }}
                </button>
            @endforeach
        </div>

        <button
            type="button"
            @click="activeModal = 'logEmergency'"
            class="min-h-[44px] inline-flex items-center justify-center gap-2 rounded-lg bg-critical-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-critical-600 focus-ring transition shrink-0"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            Log Emergency
        </button>
    </div>

    {{-- Empty state --}}
    <div x-show="filteredEmergencies.length === 0" x-cloak class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
        <p class="text-sm font-medium text-slate-600">No emergencies match this filter.</p>
        <p class="mt-1 text-xs text-slate-400">Try a different filter, or click "Log Emergency" to report an active alert.</p>
    </div>

    {{-- Emergency cards --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <template x-for="e in filteredEmergencies" :key="e.id">
            <div class="rounded-xl border bg-white p-5 shadow-sm transition"
                 :class="e.urgency === 'high' && e.status !== 'resolved' && e.status !== 'cancelled' ? 'border-critical-500/40 ring-1 ring-critical-500/20' : 'border-slate-200'">

                {{-- Header: type badge + urgency --}}
                <div class="flex items-start justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-md bg-brand-700/10 px-2.5 py-1 text-xs font-semibold text-brand-800" x-text="e.type"></span>
                        <span class="flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-semibold"
                              :class="e.urgency === 'high' ? 'bg-critical-500/10 text-critical-600' : 'bg-amber-500/10 text-amber-600'">
                            <span class="h-1.5 w-1.5 rounded-full" :class="e.urgency === 'high' ? 'bg-critical-500 animate-pulse' : 'bg-amber-500'"></span>
                            <span x-text="e.urgency === 'high' ? 'High Urgency' : 'Low Urgency'"></span>
                        </span>
                    </div>
                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold capitalize"
                          :class="{
                              'bg-amber-500/10 text-amber-600': e.status === 'pending',
                              'bg-sky-500/10 text-sky-600': e.status === 'responding',
                              'bg-emerald-500/10 text-emerald-600': e.status === 'resolved',
                              'bg-slate-500/10 text-slate-500': e.status === 'cancelled'
                          }" x-text="e.status"></span>
                </div>

                {{-- Child summary --}}
                <div class="mt-4 flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800" x-text="e.child?.avatarInitials || '??'"></div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800" x-text="e.child?.name || 'No profile on file'"></p>
                        <p class="text-sm text-slate-500" x-text="e.child ? ('Age ' + (e.child.age || '—') + ' · ' + (e.child.condition || 'General')) : 'Direct SOS alert (no profile on file)'"></p>
                        <p class="text-xs text-slate-400" x-text="'Reported ' + e.reportedAt"></p>
                    </div>
                </div>

                {{-- Location --}}
                <div class="mt-4 flex items-start gap-2 rounded-lg bg-slate-50 p-3">
                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <div class="min-w-0 text-xs">
                        <p class="font-medium text-slate-600" x-text="e.location"></p>
                        <p class="font-mono text-slate-400" x-text="e.coords"></p>
                    </div>
                </div>

                {{-- Guardian notes --}}
                <div class="mt-3 rounded-lg border border-slate-100 bg-amber-50/50 p-3">
                    <p class="text-xs font-semibold text-slate-500">Guardian Notes</p>
                    <p class="mt-1 text-sm text-slate-600" x-text="e.guardianNotes"></p>
                </div>

                {{-- Dispatched Responder Info --}}
                <div x-show="e.responder" class="mt-3 flex items-center justify-between rounded-lg border border-sky-100 bg-sky-50/70 p-3 text-xs">
                    <div class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-sky-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <div>
                            <p class="font-semibold text-sky-900" x-text="'Assigned Unit: ' + (e.responder?.name || 'Responder') + (e.responder?.unit ? ' (' + e.responder.unit + ')' : '')"></p>
                            <p class="text-sky-700 font-mono" x-text="(e.responder?.phone ? 'Phone: ' + e.responder?.phone : 'Direct unit') + (e.eta ? ' · ETA: ' + e.eta : '')"></p>
                        </div>
                    </div>
                    <span class="rounded bg-sky-200/60 px-2 py-0.5 font-medium text-sky-800" x-text="e.dispatchedAt ? 'Dispatched ' + e.dispatchedAt : (e.eta ? 'ETA ' + e.eta : 'Assigned')"></span>
                </div>

                {{-- Actions --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" @click="acknowledge(e.id)" :disabled="e.status !== 'pending'"
                            class="min-h-[44px] flex-1 rounded-lg px-3 text-sm font-semibold transition focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                            :class="e.status === 'pending' ? 'bg-slate-800 text-white hover:bg-slate-700' : 'bg-slate-100 text-slate-400'">
                        Acknowledge Alert
                    </button>
                    <button type="button" @click="openDispatchModal(e)" :disabled="e.status === 'resolved' || e.status === 'cancelled'"
                            class="min-h-[44px] flex-1 rounded-lg px-3 text-sm font-semibold transition focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                            :class="e.status !== 'resolved' && e.status !== 'cancelled' ? 'bg-brand-700 text-white hover:bg-brand-800' : 'bg-slate-100 text-slate-400'">
                        <span x-show="e.responder">Reassign Unit</span>
                        <span x-show="!e.responder">Dispatch Responder</span>
                    </button>
                    <button type="button" @click="resolve(e.id)" :disabled="e.status === 'resolved' || e.status === 'cancelled'"
                            class="min-h-[44px] flex-1 rounded-lg px-3 text-sm font-semibold transition focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                            :class="e.status !== 'resolved' && e.status !== 'cancelled' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-100 text-slate-400'">
                        <span x-show="e.status !== 'resolved' && e.status !== 'cancelled'">Mark Resolved</span>
                        <span x-show="e.status === 'resolved'">Resolved</span>
                        <span x-show="e.status === 'cancelled'">Cancelled</span>
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Log Emergency Modal Form --}}
    <div x-show="activeModal === 'logEmergency'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="activeModal = null"></div>

        <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Log Emergency Alert</h3>
                    <p class="text-xs text-slate-500">Record a crisis event and broadcast immediately to active responders.</p>
                </div>
                <button type="button" @click="activeModal = null" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitEmergency()" class="mt-4 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Child Profile <span class="text-red-500">*</span></label>
                    <select x-model="newEmergency.child_id" required class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring bg-white">
                        <option value="">-- Select enrolled child --</option>
                        <template x-for="c in children" :key="c.id">
                            <option :value="c.id" x-text="c.name + ' (' + c.condition + ')'"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Emergency Type <span class="text-red-500">*</span></label>
                        <select x-model="newEmergency.type" required class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring bg-white">
                            <option value="medical_crisis">Medical Crisis</option>
                            <option value="seizure_sensory">Seizure / Sensory</option>
                            <option value="fire">Fire / Danger</option>
                            <option value="wandering">Wandering / Elopement</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Urgency Level <span class="text-red-500">*</span></label>
                        <select x-model="newEmergency.urgency" required class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring bg-white">
                            <option value="high">High (Immediate Danger)</option>
                            <option value="low">Low (Routine Assistance)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Location <span class="text-red-500">*</span></label>
                    <input type="text" x-model="newEmergency.location" required placeholder="e.g. San Antonio Valley 1, Parañaque City" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    <p class="mt-1 text-[11px] text-slate-400">Coordinates are automatically geocoded if left blank.</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Latitude (Optional)</label>
                        <input type="number" step="any" x-model="newEmergency.lat" placeholder="14.4793" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm font-mono focus-ring">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Longitude (Optional)</label>
                        <input type="number" step="any" x-model="newEmergency.lng" placeholder="121.0198" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm font-mono focus-ring">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Assign Responder Unit (Optional)</label>
                    <select x-model="newEmergency.responder_id" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring bg-white">
                        <option value="">-- Auto / Dispatch Later --</option>
                        <template x-for="u in users.filter(usr => (usr.role || '').toLowerCase() === 'responder')" :key="u.id">
                            <option :value="u.id" x-text="u.name + ' (' + (u.phone || 'No phone') + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Guardian / Clinical Notes</label>
                    <textarea x-model="newEmergency.guardian_notes" rows="2" placeholder="Sensory triggers, medications, calming techniques..." class="w-full rounded-lg border border-slate-300 p-2.5 text-sm focus-ring"></textarea>
                </div>

                <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                    <button type="button" @click="activeModal = null" class="min-h-[42px] rounded-lg px-4 text-sm font-medium text-slate-600 hover:bg-slate-100 focus-ring">Cancel</button>
                    <button type="submit" :disabled="isSubmittingEmergency" class="min-h-[42px] inline-flex items-center gap-2 rounded-lg bg-critical-500 px-5 text-sm font-semibold text-white shadow-sm hover:bg-critical-600 focus-ring disabled:opacity-50">
                        <span x-show="!isSubmittingEmergency">Log Alert & Broadcast</span>
                        <span x-show="isSubmittingEmergency">Submitting...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Dispatch Responder Modal --}}
    <div x-show="activeModal === 'dispatchResponder'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="activeModal = null"></div>

        <div class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Dispatch Emergency Responder</h3>
                    <p class="text-xs text-slate-500">Assign a responder unit and specify estimated arrival time.</p>
                </div>
                <button type="button" @click="activeModal = null" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" stroke="currentColor" fill="none" stroke-width="2"><path d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="dispatchTarget">
                <div class="mt-4 space-y-4">
                    {{-- Emergency context summary --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 text-xs text-slate-700 space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-900" x-text="'Alert #' + dispatchTarget.id + ' · ' + dispatchTarget.type"></span>
                            <span class="rounded bg-rose-100 px-2 py-0.5 font-semibold text-rose-700 uppercase tracking-wide" x-text="dispatchTarget.urgency + ' Urgency'"></span>
                        </div>
                        <p class="text-slate-600" x-text="'Target: ' + (dispatchTarget.child?.name || 'No profile on file') + ' (' + dispatchTarget.location + ')'"></p>
                    </div>

                    <form @submit.prevent="submitDispatch()" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Select Responder Unit <span class="text-red-500">*</span></label>
                            <select x-model="dispatchForm.responder_id" required class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring bg-white">
                                <option value="">-- Select active responder --</option>
                                <template x-for="u in users.filter(usr => (usr.role || '').toLowerCase() === 'responder')" :key="u.id">
                                    <option :value="u.id" x-text="u.name + (u.unit ? ' (' + u.unit + ')' : '') + (u.phone ? ' - ' + u.phone : '')"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Estimated Time of Arrival (ETA) <span class="text-red-500">*</span></label>
                            <input type="text" x-model="dispatchForm.eta" required placeholder="e.g. 5 mins, 8 mins, 15 mins" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <template x-for="preset in ['3 mins', '5 mins', '8 mins', '10 mins', '15 mins']" :key="preset">
                                    <button type="button" @click="dispatchForm.eta = preset" class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition" :class="dispatchForm.eta === preset ? 'border-brand-600 bg-brand-50 text-brand-700 font-semibold' : ''" x-text="preset"></button>
                                </template>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400">This ETA is sent directly to the guardian's mobile app screen.</p>
                        </div>

                        <div class="mt-5 flex justify-end gap-2 border-t border-slate-100 pt-4">
                            <button type="button" @click="activeModal = null" class="min-h-[42px] rounded-lg px-4 text-sm font-medium text-slate-600 hover:bg-slate-100 focus-ring">Cancel</button>
                            <button type="submit" :disabled="isSubmittingDispatch" class="min-h-[42px] inline-flex items-center gap-2 rounded-lg bg-brand-700 px-5 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 focus-ring disabled:opacity-50">
                                <span x-show="!isSubmittingDispatch">Confirm Dispatch & Notify</span>
                                <span x-show="isSubmittingDispatch">Dispatching...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>
</div>
