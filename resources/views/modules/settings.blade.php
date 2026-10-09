{{-- ============ MODULE 6: SETTINGS & SUPPORT ============ --}}
<div class="space-y-6" x-data="{ activeGuide: 'alerts' }">

    {{-- System feedback toast --}}
    <div x-show="settingsSaveMsg" x-cloak x-transition
         class="flex items-center gap-2.5 rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 ring-1 ring-emerald-200 shadow-sm">
        <svg class="h-4 w-4 shrink-0 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span x-text="settingsSaveMsg"></span>
    </div>

    {{-- Support feedback toast --}}
    <div x-show="supportToast" x-cloak x-transition
         class="flex items-center gap-2.5 rounded-lg bg-sky-50 px-4 py-3 text-sm font-medium text-sky-800 ring-1 ring-sky-200 shadow-sm">
        <svg class="h-4 w-4 shrink-0 text-sky-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span x-text="supportToast"></span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Card 1: Alert Sound Notifications --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-5">
            <div>
                <h2 class="text-base font-bold text-slate-800">Alert Sound Notifications</h2>
                <p class="text-xs text-slate-500">Configure audible synthesizer alarms triggered by incoming emergencies.</p>
            </div>

            <div class="space-y-4 divide-y divide-slate-100">
                {{-- High Urgency Alert Toggle --}}
                <div class="flex items-center justify-between pt-1">
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                            <p class="text-sm font-semibold text-slate-800">Critical / High Urgency Alerts</p>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Pulsing alarm tone for medical crises and active wanderings.</p>
                        <button type="button" @click="playAlertSound('high')"
                                class="mt-2 inline-flex items-center gap-1.5 rounded bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-200 focus-ring">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                            Preview Alarm Sound
                        </button>
                    </div>
                    <button type="button" role="switch" :aria-checked="settings.sound_on_high"
                            @click="toggleSoundSetting('sound_on_high')"
                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus-ring"
                            :class="settings.sound_on_high ? 'bg-brand-700' : 'bg-slate-300'">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition"
                              :class="settings.sound_on_high ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>

                {{-- Low Urgency Alert Toggle --}}
                <div class="flex items-center justify-between pt-4">
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            <p class="text-sm font-semibold text-slate-800">Routine / Low Urgency Alerts</p>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Gentle chime notification for standard assistance logs.</p>
                        <button type="button" @click="playAlertSound('low')"
                                class="mt-2 inline-flex items-center gap-1.5 rounded bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-200 focus-ring">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                            Preview Gentle Chime
                        </button>
                    </div>
                    <button type="button" role="switch" :aria-checked="settings.sound_on_low"
                            @click="toggleSoundSetting('sound_on_low')"
                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition focus-ring"
                            :class="settings.sound_on_low ? 'bg-brand-700' : 'bg-slate-300'">
                        <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition"
                              :class="settings.sound_on_low ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Card 2: Emergency Hotline Configuration --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-800">Emergency Hotline Configuration</h2>
                <p class="text-xs text-slate-500">Central dispatch phone numbers displayed across alert modals and SMS dispatches.</p>
            </div>

            <div class="space-y-3.5">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">PDRRMO Emergency Desk</label>
                    <div class="relative">
                        <input type="text" x-model="settings.hotline_pdrrmo" placeholder="e.g. (02) 8820-7377"
                               class="min-h-[42px] w-full rounded-lg border border-slate-300 pl-9 pr-3 font-mono text-xs focus-ring">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Ambulance / Medical Unit</label>
                    <div class="relative">
                        <input type="text" x-model="settings.hotline_ambulance" placeholder="e.g. 911 / (02) 8825-1022"
                               class="min-h-[42px] w-full rounded-lg border border-slate-300 pl-9 pr-3 font-mono text-xs focus-ring">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                    </div>
                </div>

                <div class="pt-1">
                    <button type="button" @click="saveSettings()" :disabled="isSavingSettings"
                            class="inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-lg bg-brand-700 text-xs font-semibold text-white shadow-sm hover:bg-brand-800 focus-ring disabled:opacity-50">
                        <svg x-show="isSavingSettings" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="isSavingSettings ? 'Saving Configuration...' : 'Save Configuration'"></span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Card 3: Support, Help Desk & Operational Guide --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-800">Support Desk & Operational Guides</h2>
                    <p class="text-xs text-slate-500">Contact resources, dispatch desk contacts, and standard operating procedures (SOP).</p>
                </div>
                <button type="button" @click="activeModal = 'supportTicket'"
                        class="inline-flex min-h-[38px] items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus-ring">
                    <svg class="h-3.5 w-3.5 text-brand-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Submit Support Ticket
                </button>
            </div>

            {{-- Guide tabs --}}
            <div class="flex flex-wrap gap-2 text-xs">
                <button type="button" @click="activeGuide = 'alerts'"
                        class="rounded-lg px-3 py-1.5 font-medium transition"
                        :class="activeGuide === 'alerts' ? 'bg-brand-50 text-brand-700 font-semibold ring-1 ring-brand-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    1. Emergency Dispatch SOP
                </button>
                <button type="button" @click="activeGuide = 'privacy'"
                        class="rounded-lg px-3 py-1.5 font-medium transition"
                        :class="activeGuide === 'privacy' ? 'bg-brand-50 text-brand-700 font-semibold ring-1 ring-brand-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    2. Data Privacy (RA 10173)
                </button>
                <button type="button" @click="activeGuide = 'contacts'"
                        class="rounded-lg px-3 py-1.5 font-medium transition"
                        :class="activeGuide === 'contacts' ? 'bg-brand-50 text-brand-700 font-semibold ring-1 ring-brand-300' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                    3. Key City Contacts
                </button>
            </div>

            {{-- Guide Content: Dispatch --}}
            <div x-show="activeGuide === 'alerts'" class="rounded-lg bg-slate-50 p-4 text-xs text-slate-700 space-y-2">
                <h3 class="font-bold text-slate-800 text-sm">Emergency Dispatch Protocol</h3>
                <p>When an emergency is logged, real-time broadcasts alert all active personnel through Laravel Reverb WebSockets with synthesized audio chimes. High-urgency alerts prioritize medical crises and elopements.</p>
                <ul class="list-disc pl-5 space-y-1 text-slate-600">
                    <li><strong>Acknowledge:</strong> CSN desk confirms receipt of emergency report within 60 seconds.</li>
                    <li><strong>Dispatch:</strong> Assigns nearest PDRRMO responder unit and immediately sends automated SMS dispatch details with child medical background.</li>
                    <li><strong>Resolution:</strong> Responding unit marks alert resolved and inputs an After-Action Review (AAR) summary note for reporting.</li>
                </ul>
            </div>

            {{-- Guide Content: Privacy --}}
            <div x-show="activeGuide === 'privacy'" x-cloak class="rounded-lg bg-slate-50 p-4 text-xs text-slate-700 space-y-2">
                <h3 class="font-bold text-slate-800 text-sm">Data Privacy Act of 2012 Compliance</h3>
                <p>Sensitive developmental diagnoses and child health records are encrypted at rest using AES-256 encryption. Only authorized staff and assigned guardians may inspect confidential records.</p>
                <p class="text-slate-600">Every inspection of a child profile generates an unalterable audit log record in compliance with the National Privacy Commission (NPC) regulatory requirements.</p>
            </div>

            {{-- Guide Content: Contacts --}}
            <div x-show="activeGuide === 'contacts'" x-cloak class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs">
                <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-1">
                    <p class="font-bold text-slate-800">Parañaque CSN Center</p>
                    <p class="text-slate-500">CSN Central Coordination Desk</p>
                    <p class="font-mono text-brand-700 font-semibold">(02) 8820-CSN1</p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-1">
                    <p class="font-bold text-slate-800">PDRRMO Operations</p>
                    <p class="text-slate-500">Disaster Risk Reduction Unit</p>
                    <p class="font-mono text-rose-600 font-semibold" x-text="settings.hotline_pdrrmo || '(02) 8820-7377'"></p>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-3 space-y-1">
                    <p class="font-bold text-slate-800">Technical Support</p>
                    <p class="text-slate-500">MIS / System Administration</p>
                    <p class="font-mono text-slate-700 font-semibold">support@csn.gov.ph</p>
                </div>
            </div>
        </div>

        {{-- Card 4: ISO 27001 Audit Log (Admin Only, Tamper-Evident, Read-Only) --}}
        @if(auth()->check() && (auth()->user()->hasRole('admin') || auth()->user()->role === 'admin'))
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="mb-4 flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-bold text-slate-800">ISO 27001 Audit Trail</h2>
                            <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200">
                                Tamper-Evident &bull; Read-Only
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">Immutable chronological log of administrative, emergency, and profile activities.</p>
                    </div>
                    <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                        Admin Restricted
                    </span>
                </div>

                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <div class="overflow-x-auto max-h-80">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-50 sticky top-0">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Timestamp</th>
                                    <th class="px-4 py-2.5 text-left font-semibold text-slate-600">User / Actor</th>
                                    <th class="px-4 py-2.5 text-left font-semibold text-slate-600">Action / Event</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template x-for="(log, i) in auditLog" :key="i">
                                    <tr class="hover:bg-slate-50/80">
                                        <td class="px-4 py-2.5 font-mono text-slate-500 whitespace-nowrap" x-text="log.time"></td>
                                        <td class="px-4 py-2.5 font-medium text-slate-700" x-text="log.user"></td>
                                        <td class="px-4 py-2.5 text-slate-600" x-text="log.action"></td>
                                    </tr>
                                </template>
                                <tr x-show="auditLog.length === 0" x-cloak>
                                    <td colspan="3" class="px-4 py-8 text-center text-slate-400">No audit trail entries recorded yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

    </div>

    {{-- ============ SUBMIT SUPPORT TICKET MODAL ============ --}}
    <div x-show="activeModal === 'supportTicket'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" x-transition.opacity @click="activeModal = null"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-2xl" x-transition>
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-800">Submit Support Request</h2>
                    <p class="text-xs text-slate-500">Contact the Parañaque CSN system administration team.</p>
                </div>
                <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring" @click="activeModal = null" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitSupportTicket()" class="space-y-4 pt-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Category</label>
                    <select x-model="supportTicket.category" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-xs focus-ring">
                        <option value="technical">Technical Issue / Bug</option>
                        <option value="hotline">Hotline / Dispatch Inquiry</option>
                        <option value="privacy">Account & Security Permission</option>
                        <option value="feature">Feedback / Suggestion</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Subject <span class="text-rose-500">*</span></label>
                    <input type="text" x-model="supportTicket.subject" required placeholder="Brief description of the request" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-xs focus-ring">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Message / Details <span class="text-rose-500">*</span></label>
                    <textarea x-model="supportTicket.message" rows="4" required placeholder="Describe the inquiry or issue..." class="w-full rounded-lg border border-slate-300 p-3 text-xs focus-ring"></textarea>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="activeModal = null" class="min-h-[42px] flex-1 rounded-lg border border-slate-300 text-xs font-semibold text-slate-600 hover:bg-slate-50 focus-ring">Cancel</button>
                    <button type="submit" :disabled="isSubmittingSupport" class="inline-flex min-h-[42px] flex-1 items-center justify-center gap-2 rounded-lg bg-brand-700 text-xs font-semibold text-white hover:bg-brand-800 focus-ring disabled:opacity-50">
                        <svg x-show="isSubmittingSupport" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="isSubmittingSupport ? 'Submitting...' : 'Submit Request'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
