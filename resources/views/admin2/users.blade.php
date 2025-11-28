@extends('admin2.layout')

@section('title', 'Users')
@section('page_title', 'User Management')

@section('content')
<div x-data="usersPage()" x-init="loadUsers()">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-4">
            <div class="relative">
                <input type="text" x-model="search" @keyup.enter="loadUsers()" 
                       class="input-field pl-10 w-64" placeholder="Search by email...">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <button @click="exportCSV()" class="btn-secondary flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Export CSV
            </button>
            <button @click="showGenerateModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Add User
            </button>
        </div>
    </div>
    
    <!-- Users Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Traffic</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="user in users" :key="user.id">
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 font-medium" x-text="user.email.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="user.email"></div>
                                        <div class="text-xs text-gray-500" x-text="'ID: ' + user.id"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="user.plan_name || 'No plan'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900" x-text="formatBytes(user.total_used || 0) + ' / ' + formatBytes(user.transfer_enable || 0)"></div>
                                <div class="w-24 bg-gray-200 rounded-full h-1.5 mt-1">
                                    <div class="bg-indigo-600 h-1.5 rounded-full" :style="'width: ' + getUsagePercent(user) + '%'"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm" :class="isExpired(user.expired_at) ? 'text-red-600' : 'text-gray-900'" 
                                      x-text="user.expired_at ? formatDate(user.expired_at) : 'Never'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(user.balance)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge" :class="user.banned ? 'badge-danger' : 'badge-success'" 
                                      x-text="user.banned ? 'Banned' : 'Active'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editUser(user)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button @click="resetSecret(user)" class="text-yellow-600 hover:text-yellow-900 mr-3">Reset Token</button>
                                <a :href="user.subscribe_url" target="_blank" class="text-green-600 hover:text-green-900">Subscribe</a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing <span x-text="(currentPage - 1) * pageSize + 1"></span> to <span x-text="Math.min(currentPage * pageSize, total)"></span> of <span x-text="total"></span> users
            </div>
            <div class="flex items-center space-x-2">
                <button @click="prevPage()" :disabled="currentPage === 1" 
                        class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                    Previous
                </button>
                <span class="text-sm text-gray-600" x-text="currentPage + ' / ' + Math.ceil(total / pageSize)"></span>
                <button @click="nextPage()" :disabled="currentPage >= Math.ceil(total / pageSize)" 
                        class="px-3 py-1 border rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                    Next
                </button>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showEditModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit User</h3>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" x-model="editForm.email" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password (leave blank to keep)</label>
                            <input type="password" x-model="editForm.password" class="input-field" placeholder="••••••••">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                            <select x-model="editForm.plan_id" class="input-field">
                                <option value="">No Plan</option>
                                <template x-for="plan in plans" :key="plan.id">
                                    <option :value="plan.id" x-text="plan.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Expire Date</label>
                            <input type="datetime-local" x-model="editForm.expired_at_formatted" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transfer Limit (GB)</label>
                            <input type="number" x-model="editForm.transfer_enable_gb" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Balance ({{ config('v2board.currency', 'CNY') }})</label>
                            <input type="number" step="0.01" x-model="editForm.balance_yuan" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Speed Limit (Mbps, 0=unlimited)</label>
                            <input type="number" x-model="editForm.speed_limit" class="input-field">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Device Limit (0=unlimited)</label>
                            <input type="number" x-model="editForm.device_limit" class="input-field">
                        </div>
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="editForm.banned" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-700">Ban this user</span>
                            </label>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                            <textarea x-model="editForm.remarks" class="input-field" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button @click="showEditModal = false" class="btn-secondary">Cancel</button>
                    <button @click="saveUser()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Generate User Modal -->
    <div x-show="showGenerateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showGenerateModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Add New User</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <div class="flex">
                            <input type="text" x-model="generateForm.email_prefix" class="input-field rounded-r-none flex-1" placeholder="username">
                            <span class="inline-flex items-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 text-sm rounded-r-lg">@</span>
                            <input type="text" x-model="generateForm.email_suffix" class="input-field rounded-l-none flex-1" placeholder="example.com">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" x-model="generateForm.password" class="input-field" placeholder="Leave blank to use email">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                        <select x-model="generateForm.plan_id" class="input-field">
                            <option value="">No Plan</option>
                            <template x-for="plan in plans" :key="plan.id">
                                <option :value="plan.id" x-text="plan.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Expire Date</label>
                        <input type="datetime-local" x-model="generateForm.expired_at" class="input-field">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="showGenerateModal = false" class="btn-secondary">Cancel</button>
                    <button @click="generateUser()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        Create User
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function usersPage() {
    return {
        users: [],
        plans: [],
        total: 0,
        currentPage: 1,
        pageSize: 15,
        search: '',
        showEditModal: false,
        showGenerateModal: false,
        editForm: {},
        generateForm: {
            email_prefix: '',
            email_suffix: '',
            password: '',
            plan_id: '',
            expired_at: ''
        },
        
        async loadUsers() {
            try {
                this.$root.loading = true;
                const params = new URLSearchParams({
                    current: this.currentPage,
                    pageSize: this.pageSize
                });
                
                if (this.search) {
                    params.append('filter[0][key]', 'email');
                    params.append('filter[0][condition]', '模糊');
                    params.append('filter[0][value]', this.search);
                }
                
                const response = await this.$root.api('/admin/user/fetch?' + params.toString());
                this.users = response.data || [];
                this.total = response.total || 0;
                
                // Load plans
                const plansResponse = await this.$root.api('/admin/plan/fetch');
                this.plans = plansResponse.data || [];
            } catch (error) {
                console.error('Failed to load users:', error);
                this.$root.showToast('Failed to load users', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadUsers();
            }
        },
        
        nextPage() {
            if (this.currentPage < Math.ceil(this.total / this.pageSize)) {
                this.currentPage++;
                this.loadUsers();
            }
        },
        
        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },
        
        formatDate(timestamp) {
            if (!timestamp) return 'Never';
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString();
        },
        
        formatMoney(cents) {
            return ((cents || 0) / 100).toFixed(2);
        },
        
        getUsagePercent(user) {
            if (!user.transfer_enable) return 0;
            return Math.min(100, Math.round((user.total_used || 0) / user.transfer_enable * 100));
        },
        
        isExpired(timestamp) {
            if (!timestamp) return false;
            return timestamp < Date.now() / 1000;
        },
        
        editUser(user) {
            this.editForm = {
                id: user.id,
                email: user.email,
                password: '',
                plan_id: user.plan_id || '',
                expired_at: user.expired_at,
                expired_at_formatted: user.expired_at ? new Date(user.expired_at * 1000).toISOString().slice(0, 16) : '',
                transfer_enable: user.transfer_enable,
                transfer_enable_gb: user.transfer_enable ? (user.transfer_enable / 1073741824).toFixed(2) : 0,
                balance: user.balance,
                balance_yuan: user.balance ? (user.balance / 100).toFixed(2) : 0,
                speed_limit: user.speed_limit || 0,
                device_limit: user.device_limit || 0,
                banned: user.banned ? true : false,
                remarks: user.remarks || ''
            };
            this.showEditModal = true;
        },
        
        async saveUser() {
            try {
                this.$root.loading = true;
                
                const data = {
                    id: this.editForm.id,
                    email: this.editForm.email,
                    plan_id: this.editForm.plan_id || null,
                    transfer_enable: parseFloat(this.editForm.transfer_enable_gb) * 1073741824,
                    balance: parseFloat(this.editForm.balance_yuan) * 100,
                    speed_limit: parseInt(this.editForm.speed_limit) || 0,
                    device_limit: parseInt(this.editForm.device_limit) || 0,
                    banned: this.editForm.banned ? 1 : 0,
                    remarks: this.editForm.remarks
                };
                
                if (this.editForm.password) {
                    data.password = this.editForm.password;
                }
                
                if (this.editForm.expired_at_formatted) {
                    data.expired_at = Math.floor(new Date(this.editForm.expired_at_formatted).getTime() / 1000);
                }
                
                await this.$root.api('/admin/user/update', 'POST', data);
                await this.loadUsers();
                this.showEditModal = false;
                this.$root.showToast('User updated successfully', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Update failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async resetSecret(user) {
            if (!confirm(`Reset subscription token for ${user.email}? The old subscription link will become invalid.`)) return;
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/user/resetSecret', 'POST', { id: user.id });
                await this.loadUsers();
                this.$root.showToast('Token reset successfully', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Reset failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async generateUser() {
            if (!this.generateForm.email_prefix || !this.generateForm.email_suffix) {
                this.$root.showToast('Please enter email address', 'error');
                return;
            }
            
            try {
                this.$root.loading = true;
                const data = {
                    email_prefix: this.generateForm.email_prefix,
                    email_suffix: this.generateForm.email_suffix,
                    password: this.generateForm.password || null,
                    plan_id: this.generateForm.plan_id || null,
                    expired_at: this.generateForm.expired_at ? Math.floor(new Date(this.generateForm.expired_at).getTime() / 1000) : null
                };
                
                await this.$root.api('/admin/user/generate', 'POST', data);
                await this.loadUsers();
                this.showGenerateModal = false;
                this.generateForm = { email_prefix: '', email_suffix: '', password: '', plan_id: '', expired_at: '' };
                this.$root.showToast('User created successfully', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Creation failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        exportCSV() {
            window.open('/api/v1/admin/user/dumpCSV?Authorization=' + encodeURIComponent(this.$root.authToken), '_blank');
        }
    }
}
</script>
@endpush
