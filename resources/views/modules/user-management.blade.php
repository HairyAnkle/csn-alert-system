{{-- ============ MODULE 4: USER MANAGEMENT ============ --}}
<div class="space-y-5" x-data="{ roleTab: 'all' }">

    {{-- Top controls: Search, Role filters, and Add User button --}}
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
                x-model="userSearch"
                placeholder="Search by name, email, or phone..."
                class="min-h-[44px] w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-700 placeholder-slate-400 focus-ring"
            >
        </div>
        <button type="button" @click="resetNewUser(); activeModal = 'addUser'"
                class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 text-sm font-semibold text-white shadow-sm hover:bg-brand-800 focus-ring">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
            Add New User
        </button>
    </div>

    {{-- Role filter tabs --}}
    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter by role">
        @foreach ([
            ['key' => 'all', 'label' => 'All Users'],
            ['key' => 'superadmin', 'label' => 'Superadmin'],
            ['key' => 'admin', 'label' => 'Client Admins'],
            ['key' => 'guardian', 'label' => 'Guardians (Mobile App)'],
            ['key' => 'responder', 'label' => 'Barangay Responders'],
        ] as $r)
            <button type="button" @click="roleTab = '{{ $r['key'] }}'"
                    class="min-h-[40px] rounded-full px-4 text-sm font-medium transition focus-ring"
                    :class="roleTab === '{{ $r['key'] }}' ? 'bg-brand-700 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'">
                {{ $r['label'] }}
            </button>
        @endforeach
    </div>

    {{-- User table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">User</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Phone</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Linked Children</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="user in filteredUsers.filter(u => roleTab === 'all' || u.role === roleTab)" :key="user.id">
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            {{-- User (Avatar, Name, Email) --}}
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white uppercase shadow-sm"
                                         :class="{
                                             'bg-sky-600': user.role === 'guardian',
                                             'bg-brand-700': user.role === 'admin',
                                             'bg-amber-600': user.role === 'responder',
                                             'bg-purple-700': user.role === 'superadmin'
                                         }"
                                         x-text="(user.name || '?').split(' ').map(n => n[0]).slice(0, 2).join('')">
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate" x-text="user.name"></p>
                                        <p class="text-xs text-slate-500 truncate" x-text="user.email"></p>
                                    </div>
                                </div>
                            </td>

                            {{-- Role badge --}}
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold capitalize"
                                      :class="{
                                          'bg-sky-50 text-sky-700 ring-1 ring-sky-200': user.role === 'guardian',
                                          'bg-brand-50 text-brand-800 ring-1 ring-brand-200': user.role === 'admin',
                                          'bg-amber-50 text-amber-700 ring-1 ring-amber-200': user.role === 'responder',
                                          'bg-purple-50 text-purple-700 ring-1 ring-purple-200': user.role === 'superadmin'
                                      }"
                                      x-text="user.role === 'superadmin' ? 'Superadmin' : (user.role === 'admin' ? 'Client Admin' : (user.role === 'responder' ? 'Barangay Responder' : (user.role === 'guardian' ? 'Guardian (Mobile)' : user.role)))"></span>
                            </td>

                            {{-- Phone --}}
                            <td class="px-4 py-3.5 font-mono text-xs text-slate-600" x-text="user.phone || '—'"></td>

                            {{-- Linked Children (for guardians) --}}
                            <td class="px-4 py-3.5">
                                <template x-if="user.role === 'guardian'">
                                    <div>
                                        <template x-if="user.children && user.children.length > 0">
                                            <div class="flex flex-wrap gap-1 max-w-xs">
                                                <template x-for="child in user.children" :key="child.id">
                                                    <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-brand-600"></span>
                                                        <span x-text="child.name"></span>
                                                    </span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!user.children || user.children.length === 0">
                                            <span class="text-xs italic text-slate-400">None linked</span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="user.role !== 'guardian'">
                                    <span class="text-xs text-slate-400">—</span>
                                </template>
                            </td>

                            {{-- Status badge --}}
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                      :class="user.is_active && user.status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200'">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="user.is_active && user.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    <span class="capitalize" x-text="user.is_active && user.status === 'active' ? 'Active' : 'Inactive'"></span>
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-4 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="openEditUser(user)"
                                            class="inline-flex min-h-[36px] items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 focus-ring">
                                        Edit
                                    </button>
                                    <button type="button" @click="resendInvite(user)" :disabled="isSendingInvite"
                                            title="Send secure setup / reset invitation link"
                                            class="inline-flex min-h-[36px] items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-600 hover:text-brand-700 hover:bg-brand-50 hover:border-brand-200 focus-ring disabled:opacity-50">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                        <span class="hidden md:inline">Invite</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredUsers.filter(u => roleTab === 'all' || u.role === roleTab).length === 0" x-cloak>
                        <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">No users found for this filter.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ ADD USER MODAL ============ --}}
    <div x-show="activeModal === 'addUser'" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" x-transition.opacity @click="activeModal = null"></div>
        <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl overflow-y-auto max-h-[90vh]" x-transition>
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Add New User</h2>
                    <p class="text-xs text-slate-500">Create an account and optionally send an email invitation.</p>
                </div>
                <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring" @click="activeModal = null" aria-label="Close">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form @submit.prevent="submitNewUser()" class="space-y-4 pt-4">
                {{-- Name & Email --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" x-model="newUser.name" required placeholder="e.g. Maria Santos" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Email Address <span class="text-rose-500">*</span></label>
                        <input type="email" x-model="newUser.email" required placeholder="user@csn.gov.ph" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>
                </div>

                {{-- Role & Phone --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">System Role <span class="text-rose-500">*</span></label>
                        <select x-model="newUser.role" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                            <option value="admin">Client Admin (Web Command Center)</option>
                            <option value="superadmin">Superadmin (System Developer / Owner)</option>
                            <option value="guardian">Guardian (Mobile App User)</option>
                            <option value="responder">Barangay Responder Unit</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Phone Number</label>
                        <input type="text" x-model="newUser.phone" placeholder="+63 9XX XXX XXXX" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>
                </div>

                {{-- Account Status --}}
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700">Account Status</label>
                    <select x-model="newUser.status" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                        <option value="active">Active (Access allowed)</option>
                        <option value="inactive">Inactive (Access blocked)</option>
                    </select>
                </div>

                {{-- Link Children (Guardian role only) --}}
                <div x-show="newUser.role === 'guardian'" x-cloak class="rounded-lg border border-sky-200 bg-sky-50/50 p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-sky-900">Link Enrolled Children</label>
                        <span class="text-[11px] text-sky-700" x-text="`${(newUser.child_ids || []).length} selected`"></span>
                    </div>
                    <p class="text-[11px] text-sky-700">Select which children this guardian is responsible for.</p>
                    <div class="max-h-36 overflow-y-auto space-y-1.5 rounded border border-sky-200/60 bg-white p-2">
                        <template x-for="c in children" :key="c.id">
                            <label class="flex items-center gap-2 rounded px-2 py-1 text-xs text-slate-700 hover:bg-slate-50 cursor-pointer">
                                <input type="checkbox" :value="c.id" x-model="newUser.child_ids" class="rounded border-slate-300 text-brand-700 focus-ring">
                                <span class="font-medium" x-text="c.name"></span>
                                <span class="text-slate-400 text-[11px]" x-text="`(${c.condition || 'General'})`"></span>
                            </label>
                        </template>
                        <div x-show="children.length === 0" class="text-xs text-slate-400 p-2 text-center">
                            No children currently enrolled.
                        </div>
                    </div>
                </div>

                {{-- Invitation flow options --}}
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3.5 space-y-3">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" x-model="newUser.send_invite" class="mt-0.5 rounded border-slate-300 text-brand-700 focus-ring">
                        <div>
                            <span class="text-xs font-semibold text-slate-800">Email secure invitation link</span>
                            <p class="text-[11px] text-slate-500">Generates a signed, temporary link allowing the user to securely set their own password upon first login.</p>
                        </div>
                    </label>

                    {{-- Manual password fallback if invite unchecked --}}
                    <div x-show="!newUser.send_invite" x-cloak class="pt-1">
                        <label class="mb-1 block text-xs font-semibold text-slate-700">Initial Password <span class="text-rose-500">*</span></label>
                        <input type="password" x-model="newUser.password" placeholder="At least 8 characters" class="min-h-[42px] w-full rounded-lg border border-slate-300 bg-white px-3 text-sm focus-ring">
                    </div>
                </div>

                <div class="flex gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="activeModal = null" class="min-h-[44px] flex-1 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus-ring">Cancel</button>
                    <button type="submit" :disabled="isCreatingUser" class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 focus-ring disabled:opacity-50">
                        <svg x-show="isCreatingUser" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="isCreatingUser ? 'Creating User...' : 'Create User'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ============ EDIT USER MODAL ============ --}}
    <div x-show="activeModal === 'editUser' && editUser !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" x-transition.opacity @click="activeModal = null; editUser = null"></div>
        <div class="relative w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl overflow-y-auto max-h-[90vh]" x-transition>
            <template x-if="editUser">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-lg font-bold text-slate-800">Edit User</h2>
                            <p class="text-xs text-slate-500" x-text="editUser.email"></p>
                        </div>
                        <button type="button" class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus-ring" @click="activeModal = null; editUser = null" aria-label="Close">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="saveUser(editUser)" class="space-y-4 pt-4">
                        {{-- Name & Email --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Full Name</label>
                                <input type="text" x-model="editUser.name" required class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Email Address</label>
                                <input type="email" x-model="editUser.email" required class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                            </div>
                        </div>

                        {{-- Role & Phone --}}
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">System Role</label>
                                <select x-model="editUser.role" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                                    <option value="admin">Client Admin (Web Command Center)</option>
                                    <option value="superadmin">Superadmin (System Developer / Owner)</option>
                                    <option value="guardian">Guardian (Mobile App User)</option>
                                    <option value="responder">Barangay Responder Unit</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-slate-700">Phone</label>
                                <input type="text" x-model="editUser.phone" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                            </div>
                        </div>

                        {{-- Account Status --}}
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700">Account Status</label>
                            <select x-model="editUser.status" class="min-h-[42px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                                <option value="active">Active (Access allowed)</option>
                                <option value="inactive">Inactive (Access blocked)</option>
                            </select>
                            <p x-show="editUser.status === 'inactive'" class="mt-1.5 text-xs text-rose-600 font-medium">
                                Warning: Inactive users cannot log in. Any active session will be revoked immediately.
                            </p>
                        </div>

                        {{-- Link Children (for guardians) --}}
                        <div x-show="editUser.role === 'guardian'" x-cloak class="rounded-lg border border-sky-200 bg-sky-50/50 p-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-semibold text-sky-900">Assigned Enrolled Children</label>
                                <span class="text-[11px] text-sky-700" x-text="`${(editUser.child_ids || []).length} selected`"></span>
                            </div>
                            <p class="text-[11px] text-sky-700">Select which children this guardian is responsible for.</p>
                            <div class="max-h-36 overflow-y-auto space-y-1.5 rounded border border-sky-200/60 bg-white p-2">
                                <template x-for="c in children" :key="c.id">
                                    <label class="flex items-center gap-2 rounded px-2 py-1 text-xs text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        <input type="checkbox" :value="c.id" x-model="editUser.child_ids" class="rounded border-slate-300 text-brand-700 focus-ring">
                                        <span class="font-medium" x-text="c.name"></span>
                                        <span class="text-slate-400 text-[11px]" x-text="`(${c.condition || 'General'})`"></span>
                                    </label>
                                </template>
                                <div x-show="children.length === 0" class="text-xs text-slate-400 p-2 text-center">
                                    No children currently enrolled.
                                </div>
                            </div>
                        </div>

                        {{-- Resend invitation notice & button --}}
                        <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <div class="text-xs text-slate-600">
                                Need to send a password setup link?
                            </div>
                            <button type="button" @click="resendInvite(editUser)" :disabled="isSendingInvite"
                                    class="inline-flex min-h-[36px] items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-50 focus-ring disabled:opacity-50">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <span x-text="isSendingInvite ? 'Sending...' : 'Send Invite Link'"></span>
                            </button>
                        </div>

                        <div class="flex gap-2 pt-2 border-t border-slate-100">
                            <button type="button" @click="activeModal = null; editUser = null" class="min-h-[44px] flex-1 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus-ring">Cancel</button>
                            <button type="submit" :disabled="isUpdatingUser" class="inline-flex min-h-[44px] flex-1 items-center justify-center gap-2 rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 focus-ring disabled:opacity-50">
                                <svg x-show="isUpdatingUser" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                <span x-text="isUpdatingUser ? 'Saving...' : 'Save Changes'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>
</div>
