{{-- ============ MODULE 4: USER MANAGEMENT ============ --}}
<div class="space-y-5" x-data="{ roleTab: 'all', editUser: null }">

    {{-- Role tabs --}}
    <div class="flex flex-wrap gap-2" role="tablist" aria-label="Filter by role">
        @foreach ([['key' => 'all', 'label' => 'All Users'], ['key' => 'guardian', 'label' => 'Guardians'], ['key' => 'staff', 'label' => 'CSN Staff / Teachers'], ['key' => 'responder', 'label' => 'Responders']] as $r)
            <button type="button" @click="roleTab = '{{ $r['key'] }}'"
                    class="min-h-[44px] rounded-full px-4 text-sm font-medium transition focus-ring"
                    :class="roleTab === '{{ $r['key'] }}' ? 'bg-brand-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'">
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
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Phone</th>
                        <th class="px-4 py-3 text-left font-semibold text-slate-500">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(user, i) in users.filter(u => roleTab === 'all' || u.role === roleTab)" :key="i">
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700" x-text="user.name"></td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2.5 py-1 text-xs font-semibold capitalize"
                                      :class="{
                                          'bg-sky-500/10 text-sky-600': user.role === 'guardian',
                                          'bg-brand-700/10 text-brand-800': user.role === 'staff',
                                          'bg-amber-500/10 text-amber-600': user.role === 'responder'
                                      }" x-text="user.role"></span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-500" x-text="user.phone"></td>
                            <td class="px-4 py-3">
                                <span class="flex items-center gap-1.5 text-xs font-medium"
                                      :class="user.status === 'active' ? 'text-emerald-600' : 'text-slate-400'">
                                    <span class="h-2 w-2 rounded-full" :class="user.status === 'active' ? 'bg-emerald-500' : 'bg-slate-300'"></span>
                                    <span class="capitalize" x-text="user.status"></span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button type="button" @click="editUser = user"
                                        class="min-h-[44px] rounded-lg px-3 text-sm font-semibold text-brand-700 hover:bg-brand-50 focus-ring">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="users.filter(u => roleTab === 'all' || u.role === roleTab).length === 0" x-cloak>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">No users found for this role yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Edit modal --}}
    <div x-show="editUser !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-slate-900/50" x-transition.opacity @click="editUser = null"></div>
        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-xl" x-transition>
            <template x-if="editUser">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-slate-800">Edit User</h2>
                        <button type="button" class="rounded-md p-2 text-slate-400 hover:bg-slate-100 focus-ring" @click="editUser = null" aria-label="Close">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Full Name</label>
                        <input type="text" x-model="editUser.name" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Phone</label>
                        <input type="text" x-model="editUser.phone" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-600">Status</label>
                        <select x-model="editUser.status" class="min-h-[44px] w-full rounded-lg border border-slate-300 px-3 text-sm focus-ring">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="flex gap-2 pt-2">
                        <button type="button" @click="editUser = null" class="min-h-[44px] flex-1 rounded-lg border border-slate-300 text-sm font-semibold text-slate-600 hover:bg-slate-50 focus-ring">Cancel</button>
                        <button type="button" @click="editUser = null" class="min-h-[44px] flex-1 rounded-lg bg-brand-700 text-sm font-semibold text-white hover:bg-brand-800 focus-ring">Save Changes</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
