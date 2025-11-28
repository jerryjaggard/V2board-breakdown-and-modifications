@extends('admin2.layout')

@section('title', 'Notices')
@section('page_title', 'Notice Management')

@section('content')
<div x-data="noticesPage()" x-init="loadNotices()">
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Manage announcements and notices for users</p>
        <button @click="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Notice
        </button>
    </div>
    
    <div class="space-y-4">
        <template x-for="notice in notices" :key="notice.id">
            <div class="card p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3">
                            <h3 class="text-lg font-medium text-gray-900" x-text="notice.title"></h3>
                            <span class="badge" :class="notice.show ? 'badge-success' : 'badge-warning'" 
                                  x-text="notice.show ? 'Published' : 'Draft'"></span>
                        </div>
                        <p class="text-sm text-gray-500 mt-2" x-text="notice.content?.substring(0, 200) + '...'"></p>
                        <p class="text-xs text-gray-400 mt-2" x-text="'Updated: ' + formatDate(notice.updated_at)"></p>
                    </div>
                    <div class="flex items-center space-x-3 ml-4">
                        <button @click="toggleNotice(notice)" class="text-sm" :class="notice.show ? 'text-yellow-600' : 'text-green-600'">
                            <span x-text="notice.show ? 'Hide' : 'Publish'"></span>
                        </button>
                        <button @click="deleteNotice(notice)" class="text-sm text-red-600">Delete</button>
                    </div>
                </div>
            </div>
        </template>
        
        <div x-show="notices.length === 0" class="text-center py-12">
            <p class="text-gray-500">No notices found</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function noticesPage() {
    return {
        notices: [],
        showAddModal: false,
        
        async loadNotices() {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/notice/fetch');
                this.notices = response.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load notices', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        formatDate(timestamp) {
            if (!timestamp) return '-';
            return new Date(timestamp * 1000).toLocaleString();
        },
        
        async toggleNotice(notice) {
            try {
                await this.$root.api('/admin/notice/show', 'POST', { id: notice.id });
                await this.loadNotices();
                this.$root.showToast('Notice updated', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Update failed', 'error');
            }
        },
        
        async deleteNotice(notice) {
            if (!confirm(`Delete notice "${notice.title}"?`)) return;
            try {
                await this.$root.api('/admin/notice/drop', 'POST', { id: notice.id });
                await this.loadNotices();
                this.$root.showToast('Notice deleted', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Delete failed', 'error');
            }
        }
    }
}
</script>
@endpush
