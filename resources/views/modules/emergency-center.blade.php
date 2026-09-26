{{-- ============ MODULE 2: EMERGENCY CENTER ============ --}}
<div class="space-y-5">

    {{-- Filter controls --}}
    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter emergencies">
        @php
            $filters = [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'high', 'label' => 'High Urgency'],
                ['key' => 'low', 'label' => 'Low Urgency'],
                ['key' => 'pending', 'label' => 'Pending'],
                ['key' => 'responding', 'label' => 'Responding'],
                ['key' => 'resolved', 'label' => 'Resolved'],
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

    {{-- Empty state --}}
    <div x-show="filteredEmergencies.length === 0" x-cloak class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
        <p class="text-sm font-medium text-slate-600">No emergencies match this filter.</p>
        <p class="mt-1 text-xs text-slate-400">Try a different filter, or check back once a new alert comes in.</p>
    </div>

    {{-- Emergency cards --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <template x-for="e in filteredEmergencies" :key="e.id">
            <div class="rounded-xl border bg-white p-5 shadow-sm transition"
                 :class="e.urgency === 'high' && e.status !== 'resolved' ? 'border-critical-500/40 ring-1 ring-critical-500/20' : 'border-slate-200'">

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
                              'bg-emerald-500/10 text-emerald-600': e.status === 'resolved'
                          }" x-text="e.status"></span>
                </div>

                {{-- Child summary --}}
                <div class="mt-4 flex items-start gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-800" x-text="e.child.avatarInitials"></div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800" x-text="e.child.name"></p>
                        <p class="text-sm text-slate-500" x-text="'Age ' + e.child.age + ' · ' + e.child.condition"></p>
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

                {{-- Actions --}}
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" @click="acknowledge(e.id)" :disabled="e.status !== 'pending'"
                            class="min-h-[44px] flex-1 rounded-lg px-3 text-sm font-semibold transition focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                            :class="e.status === 'pending' ? 'bg-slate-800 text-white hover:bg-slate-700' : 'bg-slate-100 text-slate-400'">
                        Acknowledge Alert
                    </button>
                    <button type="button" @click="dispatch(e.id)" :disabled="e.status === 'resolved'"
                            class="min-h-[44px] flex-1 rounded-lg px-3 text-sm font-semibold transition focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                            :class="e.status !== 'resolved' ? 'bg-brand-700 text-white hover:bg-brand-800' : 'bg-slate-100 text-slate-400'">
                        Dispatch Responder
                    </button>
                    <button type="button" @click="resolve(e.id)" :disabled="e.status === 'resolved'"
                            class="min-h-[44px] flex-1 rounded-lg px-3 text-sm font-semibold transition focus-ring disabled:cursor-not-allowed disabled:opacity-40"
                            :class="e.status !== 'resolved' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-100 text-slate-400'">
                        <span x-show="e.status !== 'resolved'">Mark Resolved</span>
                        <span x-show="e.status === 'resolved'">Resolved</span>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>
