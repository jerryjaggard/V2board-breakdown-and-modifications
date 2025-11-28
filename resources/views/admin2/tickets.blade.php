@extends('admin2.layout')

@section('title', 'Tickets')
@section('page_title', 'Support Tickets')

@section('content')
<div x-data="ticketsPage()" x-init="loadTickets()">
    <!-- Header Filters -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-4">
            <select x-model="statusFilter" @change="loadTickets()" class="input-field w-auto text-sm">
                <option value="">All Tickets</option>
                <option value="0">Open</option>
                <option value="1">Closed</option>
            </select>
            <select x-model="replyFilter" @change="loadTickets()" class="input-field w-auto text-sm">
                <option value="">All</option>
                <option value="0">Pending Reply</option>
                <option value="1">Replied</option>
            </select>
        </div>
        <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-500">
                <span class="font-medium text-red-600" x-text="pendingCount"></span> pending reply
            </span>
        </div>
    </div>
    
    <!-- Tickets Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tickets List -->
        <div class="lg:col-span-1">
            <div class="card">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="font-medium text-gray-900">Tickets</h3>
                </div>
                <div class="divide-y divide-gray-200 max-h-[600px] overflow-y-auto">
                    <template x-for="ticket in tickets" :key="ticket.id">
                        <div @click="selectTicket(ticket)" 
                             class="p-4 cursor-pointer hover:bg-gray-50 transition-colors"
                             :class="selectedTicket?.id === ticket.id ? 'bg-indigo-50 border-l-4 border-indigo-600' : ''">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="ticket.subject"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="'User #' + ticket.user_id"></p>
                                </div>
                                <div class="ml-2 flex-shrink-0">
                                    <span class="badge" :class="ticket.status === 0 ? 'badge-success' : 'badge-warning'" 
                                          x-text="ticket.status === 0 ? 'Open' : 'Closed'"></span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-gray-400" x-text="formatDate(ticket.updated_at)"></span>
                                <span x-show="ticket.reply_status === 0" class="w-2 h-2 bg-red-500 rounded-full"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="tickets.length === 0" class="p-8 text-center text-gray-500">
                        No tickets found
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
                    <button @click="prevPage()" :disabled="currentPage === 1" 
                            class="text-sm text-gray-600 disabled:opacity-50">Previous</button>
                    <span class="text-sm text-gray-500" x-text="currentPage"></span>
                    <button @click="nextPage()" :disabled="currentPage >= Math.ceil(total / pageSize)" 
                            class="text-sm text-gray-600 disabled:opacity-50">Next</button>
                </div>
            </div>
        </div>
        
        <!-- Ticket Detail -->
        <div class="lg:col-span-2">
            <div class="card" x-show="selectedTicket">
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" x-text="selectedTicket?.subject"></h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Ticket #<span x-text="selectedTicket?.id"></span> • 
                            User #<span x-text="selectedTicket?.user_id"></span> • 
                            <span x-text="selectedTicket?.level === 1 ? 'High Priority' : 'Normal'"></span>
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="closeTicket()" x-show="selectedTicket?.status === 0"
                                class="px-3 py-1.5 text-sm bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200">
                            Close Ticket
                        </button>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="p-6 space-y-4 max-h-96 overflow-y-auto" id="messagesContainer">
                    <template x-for="msg in selectedTicket?.message || []" :key="msg.id">
                        <div class="flex" :class="msg.is_me ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%] rounded-lg p-4"
                                 :class="msg.is_me ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900'">
                                <p class="text-sm whitespace-pre-wrap" x-text="msg.message"></p>
                                <p class="text-xs mt-2 opacity-70" x-text="formatDate(msg.created_at)"></p>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Reply Form -->
                <div class="px-6 py-4 border-t border-gray-200" x-show="selectedTicket?.status === 0">
                    <div class="flex space-x-4">
                        <textarea x-model="replyMessage" class="input-field flex-1" rows="3" 
                                  placeholder="Type your reply..."></textarea>
                        <button @click="sendReply()" 
                                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors self-end">
                            Send
                        </button>
                    </div>
                </div>
                
                <div x-show="selectedTicket?.status === 1" class="px-6 py-4 border-t border-gray-200 text-center">
                    <p class="text-gray-500">This ticket is closed</p>
                </div>
            </div>
            
            <!-- Empty State -->
            <div class="card p-12 text-center" x-show="!selectedTicket">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
                <p class="text-gray-500 text-lg">Select a ticket to view details</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ticketsPage() {
    return {
        tickets: [],
        selectedTicket: null,
        total: 0,
        currentPage: 1,
        pageSize: 15,
        statusFilter: '',
        replyFilter: '',
        replyMessage: '',
        pendingCount: 0,
        
        async loadTickets() {
            try {
                this.$root.loading = true;
                const params = new URLSearchParams({
                    current: this.currentPage,
                    pageSize: this.pageSize
                });
                
                if (this.statusFilter !== '') {
                    params.append('status', this.statusFilter);
                }
                
                if (this.replyFilter !== '') {
                    params.append('reply_status[]', this.replyFilter);
                }
                
                const response = await this.$root.api('/admin/ticket/fetch?' + params.toString());
                this.tickets = response.data || [];
                this.total = response.total || 0;
                
                // Count pending
                this.pendingCount = this.tickets.filter(t => t.reply_status === 0 && t.status === 0).length;
                
                // Update sidebar badge
                this.$root.pendingTickets = this.pendingCount;
            } catch (error) {
                this.$root.showToast('Failed to load tickets', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async selectTicket(ticket) {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/ticket/fetch?id=' + ticket.id);
                this.selectedTicket = response.data;
                
                // Scroll to bottom of messages
                this.$nextTick(() => {
                    const container = document.getElementById('messagesContainer');
                    if (container) container.scrollTop = container.scrollHeight;
                });
            } catch (error) {
                this.$root.showToast('Failed to load ticket', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async sendReply() {
            if (!this.replyMessage.trim()) {
                this.$root.showToast('Please enter a message', 'error');
                return;
            }
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/ticket/reply', 'POST', {
                    id: this.selectedTicket.id,
                    message: this.replyMessage
                });
                
                this.replyMessage = '';
                await this.selectTicket(this.selectedTicket);
                await this.loadTickets();
                this.$root.showToast('Reply sent', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Failed to send reply', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async closeTicket() {
            if (!confirm('Close this ticket?')) return;
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/ticket/close', 'POST', { id: this.selectedTicket.id });
                await this.loadTickets();
                this.selectedTicket.status = 1;
                this.$root.showToast('Ticket closed', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Failed to close ticket', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadTickets();
            }
        },
        
        nextPage() {
            if (this.currentPage < Math.ceil(this.total / this.pageSize)) {
                this.currentPage++;
                this.loadTickets();
            }
        },
        
        formatDate(timestamp) {
            if (!timestamp) return '-';
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        }
    }
}
</script>
@endpush
