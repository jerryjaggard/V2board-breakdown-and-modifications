@extends('admin2.layout')

@section('title', 'Orders')
@section('page_title', 'Order Management')

@section('content')
<div x-data="ordersPage()" x-init="loadOrders()">
    <!-- Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center space-x-4">
            <div class="relative">
                <input type="text" x-model="search" @keyup.enter="loadOrders()" 
                       class="input-field pl-10 w-64" placeholder="Search trade number...">
                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <select x-model="statusFilter" @change="loadOrders()" class="input-field w-auto text-sm">
                <option value="">All Status</option>
                <option value="0">Pending</option>
                <option value="1">Pending Activation</option>
                <option value="2">Cancelled</option>
                <option value="3">Completed</option>
                <option value="4">Discounted</option>
            </select>
        </div>
        <button @click="showAssignModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Assign Order
        </button>
    </div>
    
    <!-- Orders Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="order in orders" :key="order.id">
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="order.trade_no"></div>
                                <div class="text-xs text-gray-500" x-text="getOrderType(order.type)"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="'User #' + order.user_id"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="order.plan_name || 'N/A'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900" x-text="getPeriodName(order.period)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(order.total_amount)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge" :class="getStatusClass(order.status)" x-text="getStatusName(order.status)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500" x-text="formatDate(order.created_at)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="viewDetail(order)" class="text-indigo-600 hover:text-indigo-900 mr-3">Detail</button>
                                <template x-if="order.status === 0">
                                    <span>
                                        <button @click="markPaid(order)" class="text-green-600 hover:text-green-900 mr-3">Mark Paid</button>
                                        <button @click="cancelOrder(order)" class="text-red-600 hover:text-red-900">Cancel</button>
                                    </span>
                                </template>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                Showing <span x-text="(currentPage - 1) * pageSize + 1"></span> to <span x-text="Math.min(currentPage * pageSize, total)"></span> of <span x-text="total"></span> orders
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
    
    <!-- Assign Order Modal -->
    <div x-show="showAssignModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showAssignModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Assign Order to User</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User Email</label>
                        <input type="email" x-model="assignForm.email" class="input-field" placeholder="user@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
                        <select x-model="assignForm.plan_id" class="input-field">
                            <option value="">Select Plan</option>
                            <template x-for="plan in plans" :key="plan.id">
                                <option :value="plan.id" x-text="plan.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                        <select x-model="assignForm.period" class="input-field">
                            <option value="month_price">Monthly</option>
                            <option value="quarter_price">Quarterly</option>
                            <option value="half_year_price">Semi-Annual</option>
                            <option value="year_price">Annual</option>
                            <option value="two_year_price">Two Year</option>
                            <option value="three_year_price">Three Year</option>
                            <option value="onetime_price">One Time</option>
                            <option value="reset_price">Reset Traffic</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount ({{ config('v2board.currency', 'CNY') }})</label>
                        <input type="number" step="0.01" x-model="assignForm.total_amount" class="input-field" placeholder="0.00">
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button @click="showAssignModal = false" class="btn-secondary">Cancel</button>
                    <button @click="assignOrder()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        Create Order
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Detail Modal -->
    <div x-show="showDetailModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDetailModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Order Detail</h3>
                    <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto max-h-[70vh]">
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm text-gray-500">Trade Number</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="detailOrder.trade_no"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd><span class="badge" :class="getStatusClass(detailOrder.status)" x-text="getStatusName(detailOrder.status)"></span></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">User ID</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="detailOrder.user_id"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Plan</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="detailOrder.plan_name || 'N/A'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Period</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="getPeriodName(detailOrder.period)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Type</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="getOrderType(detailOrder.type)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Total Amount</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(detailOrder.total_amount)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Discount Amount</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(detailOrder.discount_amount)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Commission</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(detailOrder.commission_balance)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Callback Number</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="detailOrder.callback_no || '-'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Created At</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="formatDate(detailOrder.created_at)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Paid At</dt>
                            <dd class="text-sm font-medium text-gray-900" x-text="detailOrder.paid_at ? formatDate(detailOrder.paid_at) : '-'"></dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ordersPage() {
    return {
        orders: [],
        plans: [],
        total: 0,
        currentPage: 1,
        pageSize: 15,
        search: '',
        statusFilter: '',
        showAssignModal: false,
        showDetailModal: false,
        detailOrder: {},
        assignForm: {
            email: '',
            plan_id: '',
            period: 'month_price',
            total_amount: ''
        },
        
        async loadOrders() {
            try {
                this.$root.loading = true;
                const params = new URLSearchParams({
                    current: this.currentPage,
                    pageSize: this.pageSize
                });
                
                if (this.search) {
                    params.append('filter[0][key]', 'trade_no');
                    params.append('filter[0][condition]', '模糊');
                    params.append('filter[0][value]', this.search);
                }
                
                if (this.statusFilter !== '') {
                    params.append('filter[1][key]', 'status');
                    params.append('filter[1][condition]', '=');
                    params.append('filter[1][value]', this.statusFilter);
                }
                
                const response = await this.$root.api('/admin/order/fetch?' + params.toString());
                this.orders = response.data || [];
                this.total = response.total || 0;
                
                // Load plans
                const plansResponse = await this.$root.api('/admin/plan/fetch');
                this.plans = plansResponse.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load orders', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadOrders();
            }
        },
        
        nextPage() {
            if (this.currentPage < Math.ceil(this.total / this.pageSize)) {
                this.currentPage++;
                this.loadOrders();
            }
        },
        
        formatMoney(cents) {
            return ((cents || 0) / 100).toFixed(2);
        },
        
        formatDate(timestamp) {
            if (!timestamp) return '-';
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        },
        
        getStatusName(status) {
            const names = {
                0: 'Pending',
                1: 'Pending Activation',
                2: 'Cancelled',
                3: 'Completed',
                4: 'Discounted'
            };
            return names[status] || 'Unknown';
        },
        
        getStatusClass(status) {
            const classes = {
                0: 'badge-warning',
                1: 'badge-info',
                2: 'badge-danger',
                3: 'badge-success',
                4: 'badge-success'
            };
            return classes[status] || 'badge-info';
        },
        
        getOrderType(type) {
            const types = {
                1: 'New Purchase',
                2: 'Renewal',
                3: 'Upgrade',
                4: 'Reset Traffic'
            };
            return types[type] || 'Unknown';
        },
        
        getPeriodName(period) {
            const periods = {
                'month_price': 'Monthly',
                'quarter_price': 'Quarterly',
                'half_year_price': 'Semi-Annual',
                'year_price': 'Annual',
                'two_year_price': 'Two Year',
                'three_year_price': 'Three Year',
                'onetime_price': 'One Time',
                'reset_price': 'Reset Traffic'
            };
            return periods[period] || period;
        },
        
        async viewDetail(order) {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/order/detail?id=' + order.id);
                this.detailOrder = response.data || order;
                this.showDetailModal = true;
            } catch (error) {
                this.$root.showToast('Failed to load order detail', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async markPaid(order) {
            if (!confirm(`Mark order ${order.trade_no} as paid?`)) return;
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/order/paid', 'POST', { trade_no: order.trade_no });
                await this.loadOrders();
                this.$root.showToast('Order marked as paid', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Operation failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async cancelOrder(order) {
            if (!confirm(`Cancel order ${order.trade_no}?`)) return;
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/order/cancel', 'POST', { trade_no: order.trade_no });
                await this.loadOrders();
                this.$root.showToast('Order cancelled', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Operation failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async assignOrder() {
            if (!this.assignForm.email || !this.assignForm.plan_id) {
                this.$root.showToast('Please fill in all required fields', 'error');
                return;
            }
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/order/assign', 'POST', {
                    email: this.assignForm.email,
                    plan_id: parseInt(this.assignForm.plan_id),
                    period: this.assignForm.period,
                    total_amount: parseFloat(this.assignForm.total_amount) * 100
                });
                await this.loadOrders();
                this.showAssignModal = false;
                this.assignForm = { email: '', plan_id: '', period: 'month_price', total_amount: '' };
                this.$root.showToast('Order assigned successfully', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Operation failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        }
    }
}
</script>
@endpush
