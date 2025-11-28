@extends('admin2.layout')

@section('title', 'Coupons')
@section('page_title', 'Coupon Management')

@section('content')
<div x-data="couponsPage()" x-init="loadCoupons()">
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Create and manage discount coupons</p>
        <button @click="showAddModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors flex items-center text-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
            Add Coupon
        </button>
    </div>
    
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expires</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <template x-for="coupon in coupons" :key="coupon.id">
                        <tr class="table-row">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded" x-text="coupon.code"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="coupon.name"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-green-600" x-text="coupon.type === 1 ? formatMoney(coupon.value) + ' off' : coupon.value + '% off'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="(coupon.limit_use - coupon.used) + ' / ' + coupon.limit_use"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="coupon.ended_at ? formatDate(coupon.ended_at) : 'Never'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="badge" :class="isActive(coupon) ? 'badge-success' : 'badge-danger'" 
                                      x-text="isActive(coupon) ? 'Active' : 'Expired'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button @click="deleteCoupon(coupon)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <div x-show="coupons.length === 0" class="text-center py-12">
            <p class="text-gray-500">No coupons found</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function couponsPage() {
    return {
        coupons: [],
        showAddModal: false,
        
        async loadCoupons() {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/coupon/fetch');
                this.coupons = response.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load coupons', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        formatMoney(cents) {
            return '{{ config('v2board.currency_symbol', '¥') }}' + ((cents || 0) / 100).toFixed(2);
        },
        
        formatDate(timestamp) {
            if (!timestamp) return '-';
            return new Date(timestamp * 1000).toLocaleDateString();
        },
        
        isActive(coupon) {
            if (coupon.ended_at && coupon.ended_at < Date.now() / 1000) return false;
            if (coupon.limit_use && coupon.used >= coupon.limit_use) return false;
            return true;
        },
        
        async deleteCoupon(coupon) {
            if (!confirm(`Delete coupon "${coupon.code}"?`)) return;
            try {
                await this.$root.api('/admin/coupon/drop', 'POST', { id: coupon.id });
                await this.loadCoupons();
                this.$root.showToast('Coupon deleted', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Delete failed', 'error');
            }
        }
    }
}
</script>
@endpush
