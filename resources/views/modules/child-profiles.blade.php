{{-- ============ MODULE 3: CHILD PROFILE MANAGEMENT ============ --}}
<div class="space-y-5">

    {{-- Search + add --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-sm">
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
            <input
                type="text"
                x-model="childSearch"
                placeholder="Search by name, condition, or address"
                class="min-h-[44px] w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-700 focus-ring"
            >
        </div>
        <button type="button" @click="activeModal = 'addChild'"
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white hover:bg-brand-800 focus-ring">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            Add New Child Profile
        </button>
    </div>

    {{-- Data table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Child</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Age</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Condition</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Guardian</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Emergency Contact</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="child in filteredChildren" :key="child.id">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-800"
                                         x-text="child.name.split(' ').map(n => n[0]).join('')"></div>
                                    <span class="font-medium text-slate-700" x-text="child.name"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600" x-text="child.age"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="child.condition"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="child.guardian"></td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="child.contact"></td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click="activeChild = child; activeModal = 'viewChild'"
                                        class="min-h-[44px] rounded-lg px-3 text-sm font-semibold text-brand-700 hover:bg-brand-50 focus-ring">
                                    View Full Profile
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredChildren.length === 0" x-cloak>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No children match your search.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Slide-over: View Full Medical Profile --}}
    <div x-show="activeModal === 'viewChild'" x-cloak class="fixed inset-0 z-50" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/50" x-transition.opacity @click="activeModal = null"></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <template x-if="activeChild">
                <div class="flex h-full flex-col">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                        <h2 class="text-base font-semibold text-slate-800">Full Medical Profile</h2>
                        <button type="button" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 focus-ring" @click="activeModal = null" aria-label="Close">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-800"
                                 x-text="activeChild.name.split(' ').map(n => n[0]).join('')"></div>
                            <div>
                                <p class="text-lg font-semibold text-slate-800" x-text="activeChild.name"></p>
                                <p class="text-sm text-slate-500" x-text="'Age ' + activeChild.age"></p>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Condition</p>
                            <p class="mt-1 text-sm text-slate-700" x-text="activeChild.condition"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Guardian</p>
                            <p class="mt-1 text-sm text-slate-700" x-text="activeChild.guardian"></p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Emergency Contact</p>
                            <p class="mt-1 font-mono text-sm text-slate-700" x-text="activeChild.contact"></p>
                        </div>
                        <div class="rounded-lg bg-amber-50 p-3">
                            <p class="text-xs font-semibold text-amber-700">Primary Medical Notes</p>
                            <p class="mt-1 text-sm text-slate-700" x-text="activeChild.notes"></p>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 px-5 py-4">
                        <button type="button" @click="activeModal = null"
                                class="min-h-[44px] w-full rounded-lg bg-slate-800 text-sm font-semibold text-white hover:bg-slate-700 focus-ring">
                            Close
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Slide-over: Add New Child Profile --}}
    <div x-show="activeModal === 'addChild'" x-cloak class="fixed inset-0 z-50" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/50" x-transition.opacity @click="activeModal = null"></div>
        <div class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-xl"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-800">Add New Child Profile</h2>
                <button type="button" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 focus-ring" @click="activeModal = null" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5" x-data="{ form: { name: '', age: '', condition: '', guardian: '', contact: '', notes: '' } }">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Full Name</label>
                    <input type="text" x-model="form.name" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Age</label>
                        <input type="number" x-model="form.age" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Condition</label>
                        <input type="text" x-model="form.condition" placeholder="e.g. ASD" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Guardian Name</label>
                    <input type="text" x-model="form.guardian" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Emergency Contact</label>
                    <input type="text" x-model="form.contact" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600">Primary Medical Notes</label>
                    <textarea x-model="form.notes" rows="4" placeholder="e.g. non-verbal, severe allergies" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus-ring"></textarea>
                </div>
                {{--
                    TODO: replace this client-only push with a real submit, e.g.:
                    async function save() {
                        const res = await fetch('/children', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify(form)
                        });
                        const saved = await res.json();
                        children.push(saved);
                        activeModal = null;
                    }
                --}}
                <div class="flex gap-2 pt-2">
                    <button type="button" @click="activeModal = null" class="min-h-[44px] flex-1 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus-ring">Cancel</button>
                    <button type="button"
                            @click="children.push({ id: Date.now(), name: form.name || 'Unnamed Child', age: form.age || '—', condition: form.condition || '—', guardian: form.guardian || '—', contact: form.contact || '—', notes: form.notes || '—' }); activeModal = null"
                            class="min-h-[44px] flex-1 rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 focus-ring">
                        Save Profile
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
