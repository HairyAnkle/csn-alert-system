{{-- ============ MODULE 3: CHILD PROFILE MANAGEMENT ============ --}}
<div class="space-y-5">

    {{-- Search + add --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full sm:max-w-sm">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
            </div>
            <input
                type="text"
                x-model="childSearch"
                @input.debounce.300ms="searchChildrenServer()"
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
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Address</th>
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
                                    <template x-if="child.photoUrl">
                                        <img :src="child.photoUrl" :alt="child.name" class="h-9 w-9 rounded-full object-cover ring-1 ring-slate-200">
                                    </template>
                                    <template x-if="!child.photoUrl">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-bold text-brand-800"
                                             x-text="child.avatarInitials || child.name.split(' ').map(n => n[0]).join('')"></div>
                                    </template>
                                    <span class="font-medium text-slate-700" x-text="child.name"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600" x-text="child.age"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="child.condition"></td>
                            <td class="px-4 py-3 text-xs text-slate-500" x-text="child.address || 'Parañaque City'"></td>
                            <td class="px-4 py-3 text-slate-600" x-text="child.guardian"></td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                <p x-text="child.emergency_contact_phone || child.contact || '—'"></p>
                                <p class="text-[10px] font-sans text-slate-400" x-show="child.emergency_contact_name && child.emergency_contact_name !== 'Primary Contact'" x-text="child.emergency_contact_name"></p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click="viewChildProfile(child.id)"
                                        class="min-h-[44px] rounded-lg px-3 text-sm font-semibold text-brand-700 hover:bg-brand-50 focus-ring">
                                    View Full Profile
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredChildren.length === 0" x-cloak>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-400">No children match your search.</td>
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
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-semibold text-slate-800">Full Medical Profile</h2>
                            <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-600/20">DPA Protected</span>
                        </div>
                        <button type="button" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 focus-ring" @click="activeModal = null" aria-label="Close">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Loading state --}}
                    <div x-show="isLoadingChild" class="p-6 text-center text-sm text-slate-500">
                        <svg class="mx-auto h-6 w-6 animate-spin text-brand-700 mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        Decrypting sensitive clinical record...
                    </div>

                    {{-- Profile details --}}
                    <div x-show="!isLoadingChild" class="flex-1 space-y-5 overflow-y-auto px-5 py-5">
                        <div class="flex items-center gap-4">
                            <template x-if="activeChild.photoUrl">
                                <img :src="activeChild.photoUrl" :alt="activeChild.name" class="h-16 w-16 rounded-full object-cover ring-2 ring-brand-100">
                            </template>
                            <template x-if="!activeChild.photoUrl">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 text-lg font-bold text-brand-800"
                                     x-text="activeChild.avatarInitials || activeChild.name.split(' ').map(n => n[0]).join('')"></div>
                            </template>
                            <div>
                                <p class="text-lg font-semibold text-slate-800" x-text="activeChild.name"></p>
                                <p class="text-sm text-slate-500" x-text="'Age ' + activeChild.age + (activeChild.birthdate ? ' (' + activeChild.birthdate + ')' : '')"></p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Condition / Diagnosis</p>
                            <p class="mt-1 text-sm font-medium text-slate-700" x-text="activeChild.condition"></p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Home Address</p>
                            <p class="mt-1 text-sm text-slate-700" x-text="activeChild.address || 'Parañaque City'"></p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Parent / Guardian</p>
                                <p class="mt-1 text-sm text-slate-700" x-text="activeChild.guardian"></p>
                                <p class="text-xs text-slate-500 font-mono" x-show="activeChild.guardian_phone" x-text="activeChild.guardian_phone"></p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Emergency Contact</p>
                                <p class="mt-1 font-mono text-sm text-slate-700" x-text="activeChild.emergency_contact_phone || activeChild.contact || '—'"></p>
                                <p class="text-xs text-slate-500" x-show="activeChild.emergency_contact_name && activeChild.emergency_contact_name !== 'Primary Contact'" x-text="activeChild.emergency_contact_name"></p>
                            </div>
                        </div>

                        {{-- Decrypted Allergies Card --}}
                        <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-bold text-rose-800 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-rose-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Allergies (Decrypted)
                                </p>
                                <span class="text-[10px] text-rose-700/80 font-mono">DPA Protected</span>
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-line" x-text="activeChild.allergies || 'None reported.'"></p>
                        </div>

                        {{-- Decrypted Current Medications Card --}}
                        <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-bold text-sky-800 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-sky-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Current Medications (Decrypted)
                                </p>
                                <span class="text-[10px] text-sky-700/80 font-mono">DPA Protected</span>
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-line" x-text="activeChild.medications || 'None recorded.'"></p>
                        </div>

                        {{-- Decrypted Communication Notes Card --}}
                        <div class="rounded-xl border border-purple-200 bg-purple-50/70 p-4">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-xs font-bold text-purple-800 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-purple-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                    Communication & Behavioral Notes (Decrypted)
                                </p>
                                <span class="text-[10px] text-purple-700/80 font-mono">DPA Protected</span>
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-line" x-text="activeChild.communication_notes || 'No communication preferences recorded.'"></p>
                        </div>

                        {{-- Primary Clinical Notes --}}
                        <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-bold text-amber-800 flex items-center gap-1.5">
                                    <svg class="h-3.5 w-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                    Primary Medical Notes (Decrypted)
                                </p>
                                <span class="text-[10px] text-amber-700/80 font-mono">RA 10173 Audit Logged</span>
                            </div>
                            <p class="text-sm leading-relaxed text-slate-700 whitespace-pre-line" x-text="formatMedicalNotes(activeChild.notes)"></p>
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
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Add New Child Profile</h2>
                    <p class="text-xs text-slate-500">Sensitive health info will be stored with AES-256 encryption.</p>
                </div>
                <button type="button" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 focus-ring" @click="activeModal = null" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="saveChildProfile($el)" enctype="multipart/form-data" class="flex flex-1 flex-col justify-between overflow-hidden">
                <div class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="e.g. Mateo Santos" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Age</label>
                            <input type="number" name="age" min="0" max="100" placeholder="e.g. 8" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Birthdate</label>
                            <input type="date" name="birthdate" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Condition / Needs <span class="text-red-500">*</span></label>
                        <input type="text" name="condition" required placeholder="e.g. Autism Spectrum Disorder, Epilepsy" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Home Address</label>
                        <input type="text" name="address" placeholder="e.g. Phase 1, BF Homes, Parañaque City" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Guardian Name</label>
                            <input type="text" name="guardian_name" placeholder="Parent or primary guardian" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Guardian Account</label>
                            <select name="guardian_id" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm bg-white focus-ring">
                                <option value="">-- Link registered user --</option>
                                <template x-for="u in users.filter(usr => (usr.role || '').toLowerCase() === 'guardian')" :key="u.id">
                                    <option :value="u.id" x-text="u.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Guardian Phone</label>
                        <input type="text" name="guardian_phone" placeholder="e.g. +63 917 123 4567" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm font-mono focus-ring">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" placeholder="e.g. Dr. Maria Santos (Pediatrician)" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-600">Emergency Contact Phone</label>
                            <input type="text" name="emergency_contact_phone" placeholder="e.g. +63 918 333 4455" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm font-mono focus-ring">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Profile Photo</label>
                        <input type="file" name="photo" accept="image/*" class="w-full text-xs text-slate-500 file:mr-2 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 focus-ring">
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">
                            Known Allergies
                            <span class="text-xs text-slate-400 font-normal">(Encrypted under RA 10173)</span>
                        </label>
                        <textarea name="allergies" rows="2" placeholder="e.g. Penicillin, Peanuts, Latex, Bee stings..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus-ring"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">
                            Current Medications & Dosages
                            <span class="text-xs text-slate-400 font-normal">(Encrypted under RA 10173)</span>
                        </label>
                        <textarea name="medications" rows="2" placeholder="e.g. Levetiracetam 250mg BID, Melatonin 3mg QHS..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus-ring"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">
                            Communication & Behavioral Notes
                            <span class="text-xs text-slate-400 font-normal">(Encrypted under RA 10173)</span>
                        </label>
                        <textarea name="communication_notes" rows="2" placeholder="e.g. Non-verbal, uses PECS cards, sensitive to loud sirens, respond to calm voice..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus-ring"></textarea>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">
                            Primary Medical Notes
                            <span class="text-xs text-slate-400 font-normal">(Encrypted under RA 10173)</span>
                        </label>
                        <textarea name="medical_notes" rows="2" placeholder="Additional clinical history, physician notes, emergency protocols..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus-ring"></textarea>
                    </div>
                </div>

                <div class="flex gap-2 border-t border-slate-200 p-4">
                    <button type="button" @click="activeModal = null" class="min-h-[44px] flex-1 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus-ring">Cancel</button>
                    <button type="submit" :disabled="isSavingChild" class="min-h-[44px] inline-flex items-center justify-center gap-2 flex-1 rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 focus-ring disabled:opacity-50">
                        <span x-show="!isSavingChild">Save Profile</span>
                        <span x-show="isSavingChild">Saving...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
