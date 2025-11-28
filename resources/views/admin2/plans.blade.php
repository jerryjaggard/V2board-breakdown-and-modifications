@extends('admin2.layout')

@section('title', 'Plans')
@section('page_title', 'Subscription Plans')

@section('content')
<div x-data="plansPage()" x-init="loadPlans()">
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Manage your subscription plans and pricing</p>
        <button @click="openPlanModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Plan
        </button>
    </div>
    
    <!-- Plans Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="plan in plans" :key="plan.id">
            <div class="card hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900" x-text="plan.name"></h3>
                            <p class="text-sm text-gray-500 mt-1" x-text="plan.count + ' active users'"></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="badge" :class="plan.show ? 'badge-success' : 'badge-warning'" 
                                  x-text="plan.show ? 'Visible' : 'Hidden'"></span>
                        </div>
                    </div>
                    
                    <!-- Pricing -->
                    <div class="mt-4 space-y-2">
                        <template x-if="plan.month_price">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Monthly</span>
                                <span class="font-medium" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(plan.month_price)"></span>
                            </div>
                        </template>
                        <template x-if="plan.quarter_price">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Quarterly</span>
                                <span class="font-medium" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(plan.quarter_price)"></span>
                            </div>
                        </template>
                        <template x-if="plan.year_price">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Annual</span>
                                <span class="font-medium" x-text="'{{ config('v2board.currency_symbol', '¥') }}' + formatMoney(plan.year_price)"></span>
                            </div>
                        </template>
                    </div>
                    
                    <!-- Features -->
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center text-sm text-gray-600 mb-2">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="(plan.transfer_enable || 0) + ' GB Traffic'"></span>
                        </div>
                        <div class="flex items-center text-sm text-gray-600 mb-2">
                            <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="'Group: ' + (plan.group_id || 'None')"></span>
                        </div>
                        <template x-if="plan.speed_limit">
                            <div class="flex items-center text-sm text-gray-600 mb-2">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span x-text="plan.speed_limit + ' Mbps Limit'"></span>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between">
                    <button @click="toggleVisibility(plan)" class="text-sm text-gray-600 hover:text-indigo-600">
                        <span x-text="plan.show ? 'Hide' : 'Show'"></span>
                    </button>
                    <div class="flex space-x-3">
                        <button @click="openPlanModal(plan)" class="text-sm text-indigo-600 hover:text-indigo-800">Edit</button>
                        <button @click="deletePlan(plan)" class="text-sm text-red-600 hover:text-red-800">Delete</button>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Empty State -->
        <div x-show="plans.length === 0" class="col-span-full text-center py-12">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <p class="text-gray-500 mb-4">No plans yet</p>
            <button @click="openPlanModal()" class="text-indigo-600 hover:text-indigo-800">Create your first plan</button>
        </div>
    </div>
    
    <!-- Plan Modal -->
    <div x-show="showPlanModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showPlanModal = false"></div>
            <div class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900" x-text="planForm.id ? 'Edit Plan' : 'Create Plan'"></h3>
                </div>
                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name</label>
                            <input type="text" x-model="planForm.name" class="input-field" placeholder="Premium Plan">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Server Group ID</label>
                            <input type="number" x-model="planForm.group_id" class="input-field" placeholder="1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Traffic Limit (GB)</label>
                            <input type="number" x-model="planForm.transfer_enable" class="input-field" placeholder="100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Speed Limit (Mbps, 0=unlimited)</label>
                            <input type="number" x-model="planForm.speed_limit" class="input-field" placeholder="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Device Limit (0=unlimited)</label>
                            <input type="number" x-model="planForm.device_limit" class="input-field" placeholder="0">
                        </div>
                        
                        <div class="md:col-span-2 pt-4 border-t border-gray-200">
                            <h4 class="font-medium text-gray-900 mb-3">Pricing ({{ config('v2board.currency', 'CNY') }})</h4>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Price</label>
                            <input type="number" step="0.01" x-model="planForm.month_price" class="input-field" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quarterly Price</label>
                            <input type="number" step="0.01" x-model="planForm.quarter_price" class="input-field" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semi-Annual Price</label>
                            <input type="number" step="0.01" x-model="planForm.half_year_price" class="input-field" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Annual Price</label>
                            <input type="number" step="0.01" x-model="planForm.year_price" class="input-field" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Two Year Price</label>
                            <input type="number" step="0.01" x-model="planForm.two_year_price" class="input-field" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Three Year Price</label>
                            <input type="number" step="0.01" x-model="planForm.three_year_price" class="input-field" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">One-time Price</label>
                            <input type="number" step="0.01" x-model="planForm.onetime_price" class="input-field" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reset Price</label>
                            <input type="number" step="0.01" x-model="planForm.reset_price" class="input-field" placeholder="0.00">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description (Markdown supported)</label>
                            <textarea x-model="planForm.content" class="input-field" rows="4" placeholder="Plan features and description..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button @click="showPlanModal = false" class="btn-secondary">Cancel</button>
                    <button @click="savePlan()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        <span x-text="planForm.id ? 'Save Changes' : 'Create Plan'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function plansPage() {
    return {
        plans: [],
        showPlanModal: false,
        planForm: {},
        
        async loadPlans() {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/plan/fetch');
                this.plans = response.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load plans', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        formatMoney(cents) {
            return ((cents || 0) / 100).toFixed(2);
        },
        
        openPlanModal(plan = null) {
            if (plan) {
                this.planForm = {
                    id: plan.id,
                    name: plan.name,
                    group_id: plan.group_id,
                    transfer_enable: plan.transfer_enable,
                    speed_limit: plan.speed_limit || 0,
                    device_limit: plan.device_limit || 0,
                    month_price: plan.month_price ? (plan.month_price / 100).toFixed(2) : '',
                    quarter_price: plan.quarter_price ? (plan.quarter_price / 100).toFixed(2) : '',
                    half_year_price: plan.half_year_price ? (plan.half_year_price / 100).toFixed(2) : '',
                    year_price: plan.year_price ? (plan.year_price / 100).toFixed(2) : '',
                    two_year_price: plan.two_year_price ? (plan.two_year_price / 100).toFixed(2) : '',
                    three_year_price: plan.three_year_price ? (plan.three_year_price / 100).toFixed(2) : '',
                    onetime_price: plan.onetime_price ? (plan.onetime_price / 100).toFixed(2) : '',
                    reset_price: plan.reset_price ? (plan.reset_price / 100).toFixed(2) : '',
                    content: plan.content || ''
                };
            } else {
                this.planForm = {
                    name: '',
                    group_id: 1,
                    transfer_enable: 100,
                    speed_limit: 0,
                    device_limit: 0,
                    month_price: '',
                    quarter_price: '',
                    half_year_price: '',
                    year_price: '',
                    two_year_price: '',
                    three_year_price: '',
                    onetime_price: '',
                    reset_price: '',
                    content: ''
                };
            }
            this.showPlanModal = true;
        },
        
        async savePlan() {
            if (!this.planForm.name) {
                this.$root.showToast('Plan name is required', 'error');
                return;
            }
            
            try {
                this.$root.loading = true;
                
                const data = {
                    name: this.planForm.name,
                    group_id: parseInt(this.planForm.group_id) || 1,
                    transfer_enable: parseInt(this.planForm.transfer_enable) || 0,
                    speed_limit: parseInt(this.planForm.speed_limit) || null,
                    device_limit: parseInt(this.planForm.device_limit) || null,
                    content: this.planForm.content || null
                };
                
                // Add prices (convert to cents)
                ['month_price', 'quarter_price', 'half_year_price', 'year_price', 'two_year_price', 'three_year_price', 'onetime_price', 'reset_price'].forEach(key => {
                    if (this.planForm[key]) {
                        data[key] = Math.round(parseFloat(this.planForm[key]) * 100);
                    } else {
                        data[key] = null;
                    }
                });
                
                if (this.planForm.id) {
                    data.id = this.planForm.id;
                }
                
                await this.$root.api('/admin/plan/save', 'POST', data);
                await this.loadPlans();
                this.showPlanModal = false;
                this.$root.showToast(this.planForm.id ? 'Plan updated' : 'Plan created', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Save failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async toggleVisibility(plan) {
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/plan/update', 'POST', {
                    id: plan.id,
                    show: plan.show ? 0 : 1
                });
                await this.loadPlans();
                this.$root.showToast('Plan visibility updated', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Update failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async deletePlan(plan) {
            if (!confirm(`Delete plan "${plan.name}"? This cannot be undone.`)) return;
            
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/plan/drop', 'POST', { id: plan.id });
                await this.loadPlans();
                this.$root.showToast('Plan deleted', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Delete failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        }
    }
}
</script>
@endpush
