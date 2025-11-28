@extends('admin2.layout')

@section('title', 'Knowledge Base')
@section('page_title', 'Knowledge Base')

@section('content')
<div x-data="knowledgePage()" x-init="loadArticles()">
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Manage help articles and tutorials</p>
        <button @click="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Article
        </button>
    </div>
    
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Updated</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="article in articles" :key="article.id">
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="article.title"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="article.category || 'General'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge" :class="article.show ? 'badge-success' : 'badge-warning'" 
                                      x-text="article.show ? 'Published' : 'Draft'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(article.updated_at)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="editArticle(article)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                <button @click="deleteArticle(article)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <div x-show="articles.length === 0" class="text-center py-12">
            <p class="text-gray-500">No articles found</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function knowledgePage() {
    return {
        articles: [],
        showAddModal: false,
        
        async loadArticles() {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/knowledge/fetch');
                this.articles = response.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load articles', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        formatDate(timestamp) {
            if (!timestamp) return '-';
            return new Date(timestamp * 1000).toLocaleDateString();
        },
        
        editArticle(article) {
            this.$root.showToast('Edit feature coming soon', 'info');
        },
        
        async deleteArticle(article) {
            if (!confirm(`Delete article "${article.title}"?`)) return;
            try {
                await this.$root.api('/admin/knowledge/drop', 'POST', { id: article.id });
                await this.loadArticles();
                this.$root.showToast('Article deleted', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Delete failed', 'error');
            }
        }
    }
}
</script>
@endpush
