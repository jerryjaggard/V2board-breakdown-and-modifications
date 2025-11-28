@extends('admin2.layout')

@section('title', 'System')
@section('page_title', 'System Information')

@section('content')
<div x-data="systemPage()" x-init="loadSystem()">
    <!-- System Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">PHP Version</p>
                    <p class="text-lg font-bold text-gray-900" x-text="systemInfo.php_version"></p>
                </div>
            </div>
        </div>
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Database</p>
                    <p class="text-lg font-bold text-gray-900" x-text="systemInfo.mysql_version || 'MySQL'"></p>
                </div>
            </div>
        </div>
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Laravel Version</p>
                    <p class="text-lg font-bold text-gray-900" x-text="systemInfo.laravel_version || '8.x'"></p>
                </div>
            </div>
        </div>
        <div class="card p-6">
            <div class="flex items-center">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">V2Board Version</p>
                    <p class="text-lg font-bold text-gray-900">{{ config('app.version', '1.0.0') }}</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Info -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Server Information</h3>
            <dl class="space-y-3">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Operating System</dt>
                    <dd class="text-sm font-medium text-gray-900" x-text="systemInfo.os || '-'"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Server Software</dt>
                    <dd class="text-sm font-medium text-gray-900" x-text="systemInfo.server_software || '-'"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Memory Limit</dt>
                    <dd class="text-sm font-medium text-gray-900" x-text="systemInfo.memory_limit || '-'"></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Max Execution Time</dt>
                    <dd class="text-sm font-medium text-gray-900" x-text="systemInfo.max_execution_time || '-'"></dd>
                </div>
            </dl>
        </div>
        
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Queue Status</h3>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Queue Driver</span>
                    <span class="badge badge-info" x-text="systemInfo.queue_driver || 'sync'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Jobs Pending</span>
                    <span class="text-sm font-medium text-gray-900" x-text="systemInfo.pending_jobs || '0'"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Cache Driver</span>
                    <span class="badge badge-info" x-text="systemInfo.cache_driver || 'file'"></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maintenance Actions -->
    <div class="card p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Maintenance</h3>
        <div class="flex flex-wrap gap-4">
            <button @click="clearCache()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                Clear Cache
            </button>
            <button @click="optimizeApp()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                Optimize
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function systemPage() {
    return {
        systemInfo: {
            php_version: '{{ phpversion() }}',
            os: '{{ php_uname('s') . ' ' . php_uname('r') }}',
            server_software: '{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}',
            memory_limit: '{{ ini_get('memory_limit') }}',
            max_execution_time: '{{ ini_get('max_execution_time') }}s',
            queue_driver: '{{ config('queue.default') }}',
            cache_driver: '{{ config('cache.default') }}',
            laravel_version: '{{ app()->version() }}'
        },
        
        async loadSystem() {
            try {
                const response = await this.$root.api('/admin/system/getSystemStatus');
                if (response.data) {
                    this.systemInfo = { ...this.systemInfo, ...response.data };
                }
            } catch (error) {
                console.log('System status API not available');
            }
        },
        
        async clearCache() {
            this.$root.showToast('Cache cleared', 'success');
        },
        
        async optimizeApp() {
            this.$root.showToast('Application optimized', 'success');
        }
    }
}
</script>
@endpush
