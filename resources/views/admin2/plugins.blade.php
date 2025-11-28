@extends('admin2.layout')

@section('title', 'Plugins')
@section('page_title', 'Plugin Management')

@section('content')
<div x-data="pluginsPage()" x-init="loadPlugins()">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">
                <span x-text="plugins.filter(p => p.is_enabled).length">0</span> active plugins
            </span>
            <select x-model="filter" class="input-field w-auto text-sm">
                <option value="">All Types</option>
                <option value="feature">Feature Plugins</option>
                <option value="payment">Payment Plugins</option>
            </select>
        </div>
        <div class="flex items-center space-x-3">
            <button @click="refreshPlugins()" class="btn-secondary flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </button>
            <button @click="showUploadModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Upload Plugin
            </button>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="card p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Total Plugins</p>
                    <p class="text-xl font-bold text-gray-900" x-text="availablePlugins.length">0</p>
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
                    <p class="text-sm text-gray-500">Active</p>
                    <p class="text-xl font-bold text-green-600" x-text="plugins.filter(p => p.is_enabled).length">0</p>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Disabled</p>
                    <p class="text-xl font-bold text-yellow-600" x-text="plugins.filter(p => !p.is_enabled).length">0</p>
                </div>
            </div>
        </div>
        <div class="card p-4">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-gray-500">Available</p>
                    <p class="text-xl font-bold text-blue-600" x-text="availablePlugins.filter(p => !plugins.find(ip => ip.code === p.code)).length">0</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Installed Plugins -->
    <div class="card mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Installed Plugins</h3>
        </div>
        <div class="divide-y divide-gray-200">
            <template x-for="plugin in filteredPlugins" :key="plugin.code">
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-start space-x-4">
                            <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="text-white font-bold text-lg" x-text="plugin.name.charAt(0)"></span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <h4 class="font-semibold text-gray-900" x-text="plugin.name"></h4>
                                    <span class="badge" :class="plugin.is_enabled ? 'badge-success' : 'badge-warning'" 
                                          x-text="plugin.is_enabled ? 'Active' : 'Disabled'"></span>
                                    <span class="badge badge-info" x-text="plugin.type === 'payment' ? 'Payment' : 'Feature'"></span>
                                </div>
                                <p class="text-sm text-gray-500 mt-1" x-text="plugin.description || 'No description available'"></p>
                                <p class="text-xs text-gray-400 mt-1">Version: <span x-text="plugin.version"></span></p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 ml-16 sm:ml-0">
                            <button @click="togglePlugin(plugin)" 
                                    class="px-3 py-1.5 text-sm rounded-lg transition-colors"
                                    :class="plugin.is_enabled ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200'">
                                <span x-text="plugin.is_enabled ? 'Disable' : 'Enable'"></span>
                            </button>
                            <button @click="openConfig(plugin)" 
                                    class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                                Configure
                            </button>
                            <button @click="uninstallPlugin(plugin)" 
                                    class="px-3 py-1.5 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                Uninstall
                            </button>
                        </div>
                    </div>
                </div>
            </template>
            <div x-show="filteredPlugins.length === 0" class="p-12 text-center">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                </svg>
                <p class="text-gray-500">No plugins installed yet</p>
            </div>
        </div>
    </div>
    
    <!-- Available Plugins -->
    <div class="card">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Available Plugins</h3>
            <p class="text-sm text-gray-500 mt-1">Install plugins from the plugins directory</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
            <template x-for="plugin in uninstalledPlugins" :key="plugin.code">
                <div class="border border-gray-200 rounded-xl p-4 hover:border-indigo-300 hover:shadow-sm transition-all">
                    <div class="flex items-start space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-gray-400 to-gray-600 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-bold" x-text="plugin.name.charAt(0)"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-medium text-gray-900 truncate" x-text="plugin.name"></h4>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="'v' + plugin.version"></p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mt-3 line-clamp-2" x-text="plugin.description || 'No description'"></p>
                    <button @click="installPlugin(plugin)" 
                            class="mt-4 w-full bg-indigo-600 text-white text-sm py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center justify-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Install
                    </button>
                </div>
            </template>
            <div x-show="uninstalledPlugins.length === 0" class="col-span-full text-center py-8">
                <p class="text-gray-500">All available plugins are already installed</p>
            </div>
        </div>
    </div>
    
    <!-- Upload Modal -->
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showUploadModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Plugin</h3>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition-colors">
                    <input type="file" id="pluginFile" accept=".zip" class="hidden" @change="handleFileUpload($event)">
                    <label for="pluginFile" class="cursor-pointer">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-gray-600">Click to select or drag & drop</p>
                        <p class="text-sm text-gray-400 mt-1">ZIP file only</p>
                    </label>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="showUploadModal = false" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Config Modal -->
    <div x-show="showConfigModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showConfigModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="'Configure ' + (currentPlugin?.name || 'Plugin')"></h3>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <template x-for="(field, key) in configSchema" :key="key">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1" x-text="field.label || key"></label>
                            <template x-if="field.type === 'boolean'">
                                <label class="flex items-center">
                                    <input type="checkbox" x-model="configValues[key]" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600" x-text="field.description || ''"></span>
                                </label>
                            </template>
                            <template x-if="field.type === 'select'">
                                <select x-model="configValues[key]" class="input-field">
                                    <template x-for="option in field.options" :key="option.value">
                                        <option :value="option.value" x-text="option.label"></option>
                                    </template>
                                </select>
                            </template>
                            <template x-if="field.type === 'textarea'">
                                <textarea x-model="configValues[key]" class="input-field" rows="3" :placeholder="field.placeholder || ''"></textarea>
                            </template>
                            <template x-if="!['boolean', 'select', 'textarea'].includes(field.type)">
                                <input :type="field.type || 'text'" x-model="configValues[key]" class="input-field" :placeholder="field.placeholder || ''">
                            </template>
                            <p x-show="field.description && field.type !== 'boolean'" class="text-xs text-gray-500 mt-1" x-text="field.description"></p>
                        </div>
                    </template>
                    <p x-show="Object.keys(configSchema).length === 0" class="text-gray-500 text-center py-4">
                        This plugin has no configurable options.
                    </p>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button @click="showConfigModal = false" class="btn-secondary">Cancel</button>
                    <button @click="saveConfig()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        Save Configuration
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function pluginsPage() {
    return {
        plugins: [],
        availablePlugins: [],
        filter: '',
        showUploadModal: false,
        showConfigModal: false,
        currentPlugin: null,
        configSchema: {},
        configValues: {},
        
        get filteredPlugins() {
            if (!this.filter) return this.plugins;
            return this.plugins.filter(p => p.type === this.filter);
        },
        
        get uninstalledPlugins() {
            const installedCodes = this.plugins.map(p => p.code);
            return this.availablePlugins.filter(p => !installedCodes.includes(p.code));
        },
        
        async loadPlugins() {
            try {
                // Load installed plugins
                const response = await this.$root.api('/admin/plugin/fetch');
                if (response.data) {
                    this.plugins = response.data;
                }
                
                // Load available plugins
                const typesResponse = await this.$root.api('/admin/plugin/types');
                if (typesResponse.data) {
                    this.availablePlugins = typesResponse.data;
                }
            } catch (error) {
                console.error('Failed to load plugins:', error);
                this.$root.showToast('Failed to load plugins', 'error');
            }
        },
        
        async refreshPlugins() {
            await this.loadPlugins();
            this.$root.showToast('Plugins refreshed', 'success');
        },
        
        async installPlugin(plugin) {
            if (!confirm(`Install ${plugin.name}?`)) return;
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/plugin/install', 'POST', { code: plugin.code });
                await this.loadPlugins();
                this.$root.showToast(`${plugin.name} installed successfully`, 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Installation failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async uninstallPlugin(plugin) {
            if (!confirm(`Uninstall ${plugin.name}? This will remove all plugin data.`)) return;
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/plugin/uninstall', 'POST', { code: plugin.code });
                await this.loadPlugins();
                this.$root.showToast(`${plugin.name} uninstalled`, 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Uninstall failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async togglePlugin(plugin) {
            try {
                this.$root.loading = true;
                const endpoint = plugin.is_enabled ? '/admin/plugin/disable' : '/admin/plugin/enable';
                await this.$root.api(endpoint, 'POST', { code: plugin.code });
                await this.loadPlugins();
                this.$root.showToast(`${plugin.name} ${plugin.is_enabled ? 'disabled' : 'enabled'}`, 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Toggle failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async openConfig(plugin) {
            try {
                this.$root.loading = true;
                this.currentPlugin = plugin;
                
                // Load config schema
                const schemaResponse = await this.$root.api('/admin/plugin/config/schema?code=' + plugin.code);
                this.configSchema = schemaResponse.data || {};
                
                // Set current values
                this.configValues = plugin.config || {};
                
                // Set defaults
                Object.keys(this.configSchema).forEach(key => {
                    if (this.configValues[key] === undefined && this.configSchema[key].default !== undefined) {
                        this.configValues[key] = this.configSchema[key].default;
                    }
                });
                
                this.showConfigModal = true;
            } catch (error) {
                this.$root.showToast('Failed to load configuration', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async saveConfig() {
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/plugin/config', 'POST', {
                    code: this.currentPlugin.code,
                    config: this.configValues
                });
                await this.loadPlugins();
                this.showConfigModal = false;
                this.$root.showToast('Configuration saved', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Save failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (!file.name.endsWith('.zip')) {
                this.$root.showToast('Please select a ZIP file', 'error');
                return;
            }
            
            try {
                this.$root.loading = true;
                const formData = new FormData();
                formData.append('file', file);
                
                const response = await fetch('/api/v1/admin/plugin/upload', {
                    method: 'POST',
                    headers: {
                        'Authorization': this.$root.authToken
                    },
                    body: formData
                });
                
                const result = await response.json();
                if (!response.ok) throw new Error(result.message);
                
                await this.loadPlugins();
                this.showUploadModal = false;
                this.$root.showToast('Plugin uploaded successfully', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Upload failed', 'error');
            } finally {
                this.$root.loading = false;
                event.target.value = '';
            }
        }
    }
}
</script>
@endpush
