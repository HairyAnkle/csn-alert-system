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
                        <button type="button" @click="playAlertSound('high', true)"
                                class="mt-2 inline-flex items-center gap-1.5 rounded bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-200 focus-ring">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                            Preview Alarm Sound
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold select-none w-7 text-right"
                              :class="settings.sound_on_high ? 'text-emerald-700' : 'text-slate-400'"
                              x-text="settings.sound_on_high ? 'ON' : 'OFF'"></span>
                        <button type="button" role="switch" :aria-checked="settings.sound_on_high"
                                @click="toggleSoundSetting('sound_on_high')"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200 ease-in-out focus-ring"
                                :class="settings.sound_on_high ? 'bg-emerald-600' : 'bg-slate-300'">
                            <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 ease-in-out"
                                  :style="settings.sound_on_high ? 'transform: translateX(20px)' : 'transform: translateX(0px)'"></span>
                        </button>
                    </div>
                </div>

                {{-- Low Urgency Alert Toggle --}}
                <div class="flex items-center justify-between pt-4">
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            <p class="text-sm font-semibold text-slate-800">Routine / Low Urgency Alerts</p>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">Gentle chime notification for standard assistance logs.</p>
                        <button type="button" @click="playAlertSound('low', true)"
                                class="mt-2 inline-flex items-center gap-1.5 rounded bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-200 focus-ring">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                            Preview Gentle Chime
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-semibold select-none w-7 text-right"
                              :class="settings.sound_on_low ? 'text-emerald-700' : 'text-slate-400'"
                              x-text="settings.sound_on_low ? 'ON' : 'OFF'"></span>
                        <button type="button" role="switch" :aria-checked="settings.sound_on_low"
                                @click="toggleSoundSetting('sound_on_low')"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full p-0.5 transition-colors duration-200 ease-in-out focus-ring"
                                :class="settings.sound_on_low ? 'bg-emerald-600' : 'bg-slate-300'">
                            <span class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow-sm ring-0 transition-transform duration-200 ease-in-out"
                                  :style="settings.sound_on_low ? 'transform: translateX(20px)' : 'transform: translateX(0px)'"></span>
                        </button>
                    </div>
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
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        </div>
                        <input type="text" x-model="settings.hotline_pdrrmo" placeholder="e.g. (02) 8820-7377"
                               class="min-h-[42px] w-full rounded-lg border border-slate-300 pl-10 pr-3 font-mono text-xs focus-ring">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Ambulance / Medical Unit</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        </div>
                        <input type="text" x-model="settings.hotline_ambulance" placeholder="e.g. 911 / (02) 8825-1022"
                               class="min-h-[42px] w-full rounded-lg border border-slate-300 pl-10 pr-3 font-mono text-xs focus-ring">
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

        {{-- Card: Mobile Announcement Composer (Broadcast to Guardians) --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-800">Community Announcements & Mobile Broadcasts</h2>
                        <span class="rounded bg-brand-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-brand-700 ring-1 ring-brand-200">
                            Mobile App Inbox
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">Publish advisories, weather warnings, or center bulletins directly to guardian mobile app inboxes.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Form --}}
                <form @submit.prevent="publishAnnouncement()" class="space-y-3.5">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 uppercase tracking-wider">Announcement Title <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="newAnnouncement.title" required placeholder="e.g. Typhoon Advisory: Heavy Rain Protocol"
                               class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 uppercase tracking-wider">Broadcast Content / Body <span class="text-rose-500">*</span></label>
                        <textarea x-model="newAnnouncement.body" rows="4" required placeholder="Type the message to be received by parents and guardians in the CSN Mobile App..."
                                  class="w-full rounded-lg border border-slate-300 p-3 text-sm focus-ring"></textarea>
                    </div>

                    <div class="pt-1">
                        <button type="submit" :disabled="isPublishingAnnouncement"
                                class="inline-flex min-h-[42px] w-full items-center justify-center gap-2 rounded-lg bg-brand-700 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 focus-ring disabled:opacity-50">
                            <svg x-show="isPublishingAnnouncement" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            <svg x-show="!isPublishingAnnouncement" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                            <span x-text="isPublishingAnnouncement ? 'Broadcasting...' : 'Publish to Guardian Mobile Inbox'"></span>
                        </button>
                    </div>
                </form>

                {{-- Recent broadcasts list --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 flex flex-col">
                    <p class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Recent Broadcasts (<span x-text="announcements.length"></span>)</p>
                    <div class="flex-1 overflow-y-auto max-h-60 space-y-2 pr-1">
                        <template x-for="item in announcements" :key="item.id">
                            <div class="rounded-lg border border-slate-200 bg-white p-3 text-xs space-y-1 shadow-2xl-none">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800" x-text="item.title"></span>
                                    <span class="text-[10px] text-slate-400 font-mono" x-text="item.published_at"></span>
                                </div>
                                <p class="text-slate-600 line-clamp-2" x-text="item.body"></p>
                                <p class="text-[10px] text-brand-700 font-medium" x-text="'Posted by: ' + (item.author || 'CSN Staff')"></p>
                            </div>
                        </template>
                        <div x-show="announcements.length === 0" class="py-8 text-center text-xs text-slate-400">
                            No announcements published yet. Write a message to notify guardians.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card: Two-Way Live Support Chat Desk (Guardian Mobile App <-> CSN Coordinator) --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2 space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                        </span>
                        <h2 class="text-base font-bold text-slate-800">Parañaque CSN Center Live Support Chat</h2>
                        <span class="rounded bg-emerald-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700 ring-1 ring-emerald-200">
                            Two-Way Mobile Chat
                        </span>
                        <span x-show="unreadSupportCount > 0" x-cloak
                              class="rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold text-white"
                              x-text="unreadSupportCount + ' Unread'"></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Real-time two-way messaging with parents and guardians using the Parañaque CSN Mobile App.</p>
                </div>
                <button type="button" @click="pollSupportThreads()"
                        class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-100 focus-ring">
                    <svg class="h-3.5 w-3.5 text-brand-700" :class="isPollingSupport ? 'animate-spin' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
                    <span>Refresh Inbox</span>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-[430px]">
                {{-- Left Column: Guardian Threads List --}}
                <div class="lg:col-span-4 flex flex-col rounded-xl border border-slate-200 bg-slate-50/70 overflow-hidden">
                    <div class="p-3 border-b border-slate-200 bg-white">
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            <input type="text" x-model="supportSearch" placeholder="Search guardian or child..."
                                   class="min-h-[36px] w-full rounded-lg border border-slate-200 bg-slate-50 pl-9 pr-3 text-xs text-slate-700 placeholder-slate-400 focus-ring">
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto max-h-[380px] divide-y divide-slate-200/70">
                        <template x-for="thread in filteredSupportThreads" :key="thread.guardian_id">
                            <button type="button"
                                    @click="selectSupportThread(thread.guardian_id)"
                                    class="w-full text-left p-3.5 transition flex items-start gap-3 hover:bg-white focus-ring"
                                    :class="activeSupportThread && activeSupportThread.guardian_id === thread.guardian_id ? 'bg-white border-l-4 border-l-brand-700 shadow-xs' : ''">
                                <div class="relative flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-white"
                                     x-text="thread.avatar_initials">
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <p class="truncate text-xs font-bold text-slate-800" x-text="thread.guardian_name"></p>
                                        <span x-show="thread.unread_count > 0"
                                              class="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-600 px-1.5 text-[10px] font-bold text-white"
                                              x-text="thread.unread_count"></span>
                                    </div>
                                    <p class="truncate text-[11px] font-medium text-brand-700"
                                       x-text="'Child: ' + thread.child_name + (thread.child_condition && thread.child_condition !== '—' ? ' (' + thread.child_condition + ')' : '')"></p>
                                    <p class="mt-1 truncate text-[11px] text-slate-500"
                                       :class="thread.unread_count > 0 ? 'font-semibold text-slate-800' : ''"
                                       x-text="thread.last_message_text"></p>
                                </div>
                            </button>
                        </template>

                        <div x-show="filteredSupportThreads.length === 0" class="p-6 text-center text-xs text-slate-400">
                            No guardian conversations found.
                        </div>
                    </div>
                </div>

                {{-- Right Column: Active Conversation Window --}}
                <div class="lg:col-span-8 flex flex-col rounded-xl border border-slate-200 bg-white overflow-hidden">
                    <template x-if="activeSupportThread">
                        <div class="flex flex-1 flex-col h-full">
                            {{-- Conversation Header --}}
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-white"
                                         x-text="activeSupportThread.avatar_initials"></div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-bold text-slate-800" x-text="activeSupportThread.guardian_name"></h3>
                                            <span class="rounded bg-sky-50 px-2 py-0.5 text-[10px] font-semibold text-sky-700 ring-1 ring-sky-200"
                                                  x-text="activeSupportThread.guardian_phone"></span>
                                        </div>
                                        <p class="text-xs text-slate-500"
                                           x-text="'Child: ' + activeSupportThread.child_name + ' · ' + activeSupportThread.child_condition"></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <template x-if="activeSupportThread.child_id">
                                        <button type="button" @click="viewChildProfile(activeSupportThread.child_id)"
                                                class="inline-flex min-h-[34px] items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-semibold text-brand-700 hover:bg-brand-50 focus-ring">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                            <span>Child Medical File</span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Messages Feed --}}
                            <div id="support-chat-scroll" class="flex-1 overflow-y-auto max-h-[280px] min-h-[240px] p-4 space-y-3 bg-slate-50/50">
                                <template x-if="!activeSupportThread.messages || activeSupportThread.messages.length === 0">
                                    <div class="flex h-48 flex-col items-center justify-center text-center text-xs text-slate-400">
                                        <p class="font-semibold text-slate-600">No messages in this conversation yet.</p>
                                        <p class="mt-1">Send a message below to reach <span x-text="activeSupportThread.guardian_name"></span> on the CSN Mobile App.</p>
                                    </div>
                                </template>

                                <template x-for="msg in (activeSupportThread.messages || [])" :key="msg.id">
                                    <div class="flex flex-col" :class="msg.sender === 'support' ? 'items-end' : 'items-start'">
                                        <div class="max-w-[82%] rounded-2xl px-3.5 py-2.5 text-xs shadow-xs"
                                             :class="msg.sender === 'support'
                                                ? 'bg-brand-700 text-white rounded-br-xs'
                                                : 'bg-white border border-slate-200 text-slate-800 rounded-bl-xs'">
                                            <div class="flex items-center justify-between gap-3 mb-1">
                                                <span class="font-bold text-[10px]"
                                                      :class="msg.sender === 'support' ? 'text-brand-100' : 'text-brand-700'"
                                                      x-text="msg.sender_name"></span>
                                                <span class="text-[10px] opacity-75"
                                                      x-text="msg.time_formatted || ''"></span>
                                            </div>
                                            <p class="leading-relaxed whitespace-pre-line" x-text="msg.text"></p>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Quick Reply Chips & Input Form --}}
                            <div class="border-t border-slate-200 bg-white p-3 space-y-2.5">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="preset in [
                                        'Mabuhay! How can the Parañaque CSN Desk assist you today?',
                                        'We have noted your child’s update in our CSN Coordination Desk.',
                                        'A CSN Social Welfare Coordinator will call your phone number shortly.',
                                        'For immediate medical or wandering emergencies, please press the red SOS button.'
                                    ]" :key="preset">
                                        <button type="button" @click="supportReplyText = preset"
                                                class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 transition"
                                                x-text="preset"></button>
                                    </template>
                                </div>

                                <form @submit.prevent="sendSupportReply()" class="flex items-center gap-2">
                                    <input type="text"
                                           x-model="supportReplyText"
                                           placeholder="Type your reply to the guardian's mobile app..."
                                           class="min-h-[42px] flex-1 rounded-lg border border-slate-300 px-3.5 text-xs text-slate-800 placeholder-slate-400 focus-ring">
                                    <button type="submit"
                                            :disabled="isSendingSupportReply || !supportReplyText.trim()"
                                            class="inline-flex min-h-[42px] items-center justify-center gap-1.5 rounded-lg bg-brand-700 px-4 text-xs font-semibold text-white shadow-sm hover:bg-brand-800 focus-ring disabled:opacity-50">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                        <span x-text="isSendingSupportReply ? 'Sending...' : 'Send Reply'"></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </template>

                    <template x-if="!activeSupportThread">
                        <div class="flex flex-1 items-center justify-center p-8 text-center text-xs text-slate-400">
                            Select a guardian conversation on the left to view and reply to support messages.
                        </div>
                    </template>
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
                <h3 class="font-bold text-slate-800 text-sm">Emergency Dispatch &amp; Barangay Coordination Protocol</h3>
                <p>When an emergency is triggered from the Guardian Mobile App, real-time broadcasts alert the Web Command Center with synthesized audio chimes. High-urgency alerts prioritize medical crises and elopements.</p>
                <ul class="list-disc pl-5 space-y-1 text-slate-600">
                    <li><strong>Acknowledge:</strong> Client Admin confirms receipt of the emergency alert within 60 seconds and inspects the child's decrypted medical profile and sensory de-escalation protocol.</li>
                    <li><strong>Coordinate &amp; Dispatch Barangay Responder:</strong> Admin calls the guardian's allocated or nearest Parañaque Barangay Emergency Response Team, briefs them on the sensory protocol, and configures the agreed <strong>Estimated Time of Arrival (ETA)</strong> in the Dispatch Modal to immediately notify the Guardian's Mobile App.</li>
                    <li><strong>Resolution:</strong> Once the Barangay responders confirm on-site stabilization, Admin marks the incident resolved and logs an After-Action Review (AAR) summary note for archival reporting.</li>
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

        {{-- Card 4: ISO 27001 Audit Log (Superadmin Only, Tamper-Evident, Read-Only) --}}
        @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->role === 'superadmin'))
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
