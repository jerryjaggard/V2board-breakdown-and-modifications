@extends('admin2.layout')

@section('title', 'Payments')
@section('page_title', 'Payment Methods')

@section('content')
<div x-data="paymentsPage()" x-init="loadPayments()">
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Configure payment gateways for your users</p>
        <button @click="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Payment
        </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="payment in payments" :key="payment.id">
            <div class="card hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-teal-600 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="font-semibold text-gray-900" x-text="payment.name"></h3>
                                <p class="text-sm text-gray-500" x-text="payment.payment"></p>
                            </div>
                        </div>
                        <span class="badge" :class="payment.enable ? 'badge-success' : 'badge-warning'" 
                              x-text="payment.enable ? 'Active' : 'Disabled'"></span>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs text-gray-500 truncate" x-text="'Notify: ' + payment.notify_url"></p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between">
                    <button @click="togglePayment(payment)" class="text-sm" :class="payment.enable ? 'text-yellow-600' : 'text-green-600'">
                        <span x-text="payment.enable ? 'Disable' : 'Enable'"></span>
                    </button>
                    <div class="flex space-x-3">
                        <button @click="editPayment(payment)" class="text-sm text-indigo-600">Edit</button>
                        <button @click="deletePayment(payment)" class="text-sm text-red-600">Delete</button>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <div x-show="payments.length === 0" class="text-center py-12">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
        </svg>
        <p class="text-gray-500">No payment methods configured</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
function paymentsPage() {
    return {
        payments: [],
        showAddModal: false,
        
        async loadPayments() {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/payment/fetch');
                this.payments = response.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load payments', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async togglePayment(payment) {
            try {
                await this.$root.api('/admin/payment/show', 'POST', { id: payment.id });
                await this.loadPayments();
                this.$root.showToast('Payment updated', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Update failed', 'error');
            }
        },
        
        editPayment(payment) {
            this.$root.showToast('Edit feature coming soon', 'info');
        },
        
        async deletePayment(payment) {
            if (!confirm(`Delete payment "${payment.name}"?`)) return;
            try {
                await this.$root.api('/admin/payment/drop', 'POST', { id: payment.id });
                await this.loadPayments();
                this.$root.showToast('Payment deleted', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Delete failed', 'error');
            }
        }
    }
}
</script>
@endpush
