@extends('admin2.layout')

@section('title', 'Servers')
@section('page_title', 'Server Management')

@section('content')
<div x-data="serversPage()" x-init="loadServers()">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-4">
            <select x-model="serverType" @change="loadServers()" class="input-field w-auto text-sm">
                <option value="">All Types</option>
                <option value="vmess">VMess</option>
                <option value="shadowsocks">Shadowsocks</option>
                <option value="trojan">Trojan</option>
                <option value="hysteria">Hysteria</option>
            </select>
        </div>
        <div class="flex items-center space-x-3">
            <button @click="showGroupModal = true" class="btn-secondary flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Groups
            </button>
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Add Server
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10">
                    <a href="#" @click.prevent="openServerModal('vmess'); open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">VMess Server</a>
                    <a href="#" @click.prevent="openServerModal('shadowsocks'); open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Shadowsocks Server</a>
                    <a href="#" @click.prevent="openServerModal('trojan'); open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Trojan Server</a>
                    <a href="#" @click.prevent="openServerModal('hysteria'); open = false" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Hysteria Server</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Server Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Total Servers</p>
                    <p class="text-xl font-bold text-gray-900" x-text="servers.length">0</p>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Online</p>
                    <p class="text-xl font-bold text-green-600" x-text="servers.filter(s => isOnline(s)).length">0</p>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Offline</p>
                    <p class="text-xl font-bold text-red-600" x-text="servers.filter(s => !isOnline(s)).length">0</p>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Groups</p>
                    <p class="text-xl font-bold text-purple-600" x-text="groups.length">0</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Servers Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Server</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Group</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="server in filteredServers" :key="server.type + '-' + server.id">
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" 
                                         :class="getTypeColor(server.type)">
                                        <span class="text-white font-bold text-xs" x-text="server.type.substring(0,2).toUpperCase()"></span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="server.name"></div>
                                        <div class="text-xs text-gray-500" x-text="'ID: ' + server.id"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge badge-info" x-text="server.type"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="server.host + ':' + server.port"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500" x-text="getGroupName(server.group_id)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="server.rate + 'x'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge" :class="isOnline(server) ? 'badge-success' : 'badge-danger'" 
                                      x-text="isOnline(server) ? 'Online' : 'Offline'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editServer(server)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button @click="copyServer(server)" class="text-green-600 hover:text-green-900 mr-3">Copy</button>
                                <button @click="deleteServer(server)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <div x-show="filteredServers.length === 0" class="text-center py-12">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"/>
            </svg>
            <p class="text-gray-500">No servers found</p>
        </div>
    </div>
    
    <!-- Server Modal -->
    <div x-show="showServerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showServerModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="(serverForm.id ? 'Edit' : 'Add') + ' ' + serverForm.type.toUpperCase() + ' Server'"></h3>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server Name</label>
                            <input type="text" x-model="serverForm.name" class="input-field" placeholder="US Server #1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Group</label>
                            <select x-model="serverForm.group_id" class="input-field">
                                <template x-for="group in groups" :key="group.id">
                                    <option :value="group.id" x-text="group.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rate Multiplier</label>
                            <input type="number" step="0.1" x-model="serverForm.rate" class="input-field" placeholder="1.0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server Address</label>
                            <input type="text" x-model="serverForm.host" class="input-field" placeholder="server.example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                            <input type="number" x-model="serverForm.port" class="input-field" placeholder="443">
                        </div>
                        
                        <!-- Type-specific fields -->
                        <template x-if="serverForm.type === 'vmess'">
                            <div class="md:col-span-2 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">TLS</label>
                                    <select x-model="serverForm.tls" class="input-field">
                                        <option value="0">Disabled</option>
                                        <option value="1">Enabled</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Network</label>
                                    <select x-model="serverForm.network" class="input-field">
                                        <option value="tcp">TCP</option>
                                        <option value="ws">WebSocket</option>
                                        <option value="grpc">gRPC</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                        
                        <template x-if="serverForm.type === 'shadowsocks'">
                            <div class="md:col-span-2 grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cipher</label>
                                    <select x-model="serverForm.cipher" class="input-field">
                                        <option value="aes-256-gcm">aes-256-gcm</option>
                                        <option value="aes-128-gcm">aes-128-gcm</option>
                                        <option value="chacha20-ietf-poly1305">chacha20-ietf-poly1305</option>
                                    </select>
                                </div>
                            </div>
                        </template>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tags (comma separated)</label>
                            <input type="text" x-model="serverForm.tags" class="input-field" placeholder="premium, fast">
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button @click="showServerModal = false" class="btn-secondary">Cancel</button>
                    <button @click="saveServer()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        <span x-text="serverForm.id ? 'Save Changes' : 'Add Server'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Groups Modal -->
    <div x-show="showGroupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showGroupModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Server Groups</h3>
                <div class="space-y-3 max-h-64 overflow-y-auto mb-4">
                    <template x-for="group in groups" :key="group.id">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <span class="text-sm font-medium" x-text="group.name"></span>
                            <button @click="deleteGroup(group)" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                        </div>
                    </template>
                </div>
                <div class="flex space-x-2">
                    <input type="text" x-model="newGroupName" class="input-field flex-1" placeholder="New group name">
                    <button @click="addGroup()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">Add</button>
                </div>
                <div class="flex justify-end mt-4">
                    <button @click="showGroupModal = false" class="btn-secondary">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function serversPage() {
    return {
        servers: [],
        groups: [],
        serverType: '',
        showServerModal: false,
        showGroupModal: false,
        serverForm: { type: 'vmess' },
        newGroupName: '',
        
        get filteredServers() {
            if (!this.serverType) return this.servers;
            return this.servers.filter(s => s.type === this.serverType);
        },
        
        async loadServers() {
            try {
                this.$root.loading = true;
                
                // Load servers
                const response = await this.$root.api('/admin/server/manage/getNodes');
                this.servers = response.data || [];
                
                // Load groups
                const groupsResponse = await this.$root.api('/admin/server/group/fetch');
                this.groups = groupsResponse.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load servers', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        isOnline(server) {
            if (!server.last_check_at) return false;
            return (Date.now() / 1000 - server.last_check_at) < 300;
        },
        
        getTypeColor(type) {
            const colors = {
                'vmess': 'bg-blue-600',
                'shadowsocks': 'bg-purple-600',
                'trojan': 'bg-green-600',
                'hysteria': 'bg-orange-600'
            };
            return colors[type] || 'bg-gray-600';
        },
        
        getGroupName(groupId) {
            const group = this.groups.find(g => g.id === groupId);
            return group ? group.name : 'Unknown';
        },
        
        openServerModal(type) {
            this.serverForm = {
                type: type,
                name: '',
                group_id: this.groups[0]?.id || 1,
                rate: 1,
                host: '',
                port: 443,
                tls: 1,
                network: 'tcp',
                cipher: 'aes-256-gcm',
                tags: ''
            };
            this.showServerModal = true;
        },
        
        editServer(server) {
            this.serverForm = { ...server };
            this.showServerModal = true;
        },
        
        copyServer(server) {
            this.serverForm = { ...server, id: undefined, name: server.name + ' (Copy)' };
            this.showServerModal = true;
        },
        
        async saveServer() {
            if (!this.serverForm.name || !this.serverForm.host) {
                this.$root.showToast('Name and address are required', 'error');
                return;
            }
            
            try {
                this.$root.loading = true;
                const endpoint = `/admin/server/${this.serverForm.type}/save`;
                await this.$root.api(endpoint, 'POST', this.serverForm);
                await this.loadServers();
                this.showServerModal = false;
                this.$root.showToast('Server saved', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Save failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async deleteServer(server) {
            if (!confirm(`Delete server "${server.name}"?`)) return;
            
            try {
                this.$root.loading = true;
                const endpoint = `/admin/server/${server.type}/drop`;
                await this.$root.api(endpoint, 'POST', { id: server.id });
                await this.loadServers();
                this.$root.showToast('Server deleted', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Delete failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async addGroup() {
            if (!this.newGroupName) return;
            
            try {
                await this.$root.api('/admin/server/group/save', 'POST', { name: this.newGroupName });
                await this.loadServers();
                this.newGroupName = '';
                this.$root.showToast('Group added', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Failed to add group', 'error');
            }
        },
        
        async deleteGroup(group) {
            if (!confirm(`Delete group "${group.name}"?`)) return;
            
            try {
                await this.$root.api('/admin/server/group/drop', 'POST', { id: group.id });
                await this.loadServers();
                this.$root.showToast('Group deleted', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Delete failed', 'error');
            }
        }
    }
}
</script>
@endpush
