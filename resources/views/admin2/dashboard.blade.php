@extends('admin2.layout')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div x-data="dashboardPage()" x-init="loadData()">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <!-- Today's Income -->
        <div class="stats-card card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Today's Income</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ config('v2board.currency_symbol', '¥') }}<span x-text="formatMoney(stats.day_income)">0.00</span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                This month: {{ config('v2board.currency_symbol', '¥') }}<span x-text="formatMoney(stats.month_income)">0.00</span>
            </p>
        </div>
        
        <!-- Monthly Registrations -->
        <div class="stats-card card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Monthly Registrations</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900" x-text="stats.month_register_total">0</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">New users this month</p>
        </div>
        
        <!-- Pending Tickets -->
        <div class="stats-card card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Pending Tickets</p>
                    <p class="mt-2 text-3xl font-bold" :class="stats.ticket_pending_total > 0 ? 'text-red-600' : 'text-gray-900'" x-text="stats.ticket_pending_total">0</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                </div>
            </div>
            <a href="/{{ $secure_path }}/admin2/tickets" class="mt-4 text-sm text-indigo-600 hover:text-indigo-800 flex items-center">
                View all tickets
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>
        
        <!-- Pending Commissions -->
        <div class="stats-card card p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Pending Commissions</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900" x-text="stats.commission_pending_total">0</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
            <p class="mt-4 text-sm text-gray-500">
                Last month payout: {{ config('v2board.currency_symbol', '¥') }}<span x-text="formatMoney(stats.commission_last_month_payout)">0.00</span>
            </p>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Income Chart -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Income Overview (Last 30 Days)</h3>
            <canvas id="incomeChart" height="200"></canvas>
        </div>
        
        <!-- Server Traffic Ranking -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Server Traffic Ranking</h3>
            <div class="space-y-3">
                <template x-for="(server, index) in serverRank" :key="index">
                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold"
                              :class="index < 3 ? 'bg-indigo-100 text-indigo-600' : 'bg-gray-100 text-gray-600'"
                              x-text="index + 1"></span>
                        <span class="ml-3 flex-1 text-sm text-gray-700 truncate" x-text="server.server_name || 'Unknown'"></span>
                        <span class="text-sm font-medium text-gray-900" x-text="server.total.toFixed(2) + ' GB'"></span>
                    </div>
                </template>
                <p x-show="serverRank.length === 0" class="text-sm text-gray-500 text-center py-4">No data available</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Income Comparison -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Income Comparison</h3>
            <div class="space-y-4">
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">This Month</span>
                        <span class="font-medium text-gray-900">{{ config('v2board.currency_symbol', '¥') }}<span x-text="formatMoney(stats.month_income)">0.00</span></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" :style="'width: ' + getPercentage(stats.month_income, stats.last_month_income) + '%'"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Last Month</span>
                        <span class="font-medium text-gray-900">{{ config('v2board.currency_symbol', '¥') }}<span x-text="formatMoney(stats.last_month_income)">0.00</span></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gray-400 h-2 rounded-full" :style="'width: ' + getPercentage(stats.last_month_income, stats.month_income) + '%'"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Commission Stats -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Commission Payouts</h3>
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">This Month</span>
                    <span class="text-lg font-bold text-green-600">{{ config('v2board.currency_symbol', '¥') }}<span x-text="formatMoney(stats.commission_month_payout)">0.00</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Last Month</span>
                    <span class="text-lg font-medium text-gray-900">{{ config('v2board.currency_symbol', '¥') }}<span x-text="formatMoney(stats.commission_last_month_payout)">0.00</span></span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="/{{ $secure_path }}/admin2/users" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    <span class="text-xs text-gray-700">Add User</span>
                </a>
                <a href="/{{ $secure_path }}/admin2/plans" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <span class="text-xs text-gray-700">New Plan</span>
                </a>
                <a href="/{{ $secure_path }}/admin2/servers" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"/>
                    </svg>
                    <span class="text-xs text-gray-700">Add Server</span>
                </a>
                <a href="/{{ $secure_path }}/admin2/plugins" class="flex flex-col items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                    <span class="text-xs text-gray-700">Plugins</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardPage() {
    return {
        stats: {
            day_income: 0,
            month_income: 0,
            last_month_income: 0,
            month_register_total: 0,
            ticket_pending_total: 0,
            commission_pending_total: 0,
            commission_month_payout: 0,
            commission_last_month_payout: 0
        },
        orderStats: [],
        serverRank: [],
        incomeChart: null,
        
        async loadData() {
            try {
                // Load override stats
                const overrideResponse = await this.$root.api('/admin/stat/getOverride');
                if (overrideResponse.data) {
                    this.stats = overrideResponse.data;
                    this.$root.pendingTickets = this.stats.ticket_pending_total || 0;
                }
                
                // Load order stats
                const orderResponse = await this.$root.api('/admin/stat/getOrder');
                if (orderResponse.data) {
                    this.orderStats = orderResponse.data;
                    this.renderIncomeChart();
                }
                
                // Load server rank
                const serverResponse = await this.$root.api('/admin/stat/getServerLastRank');
                if (serverResponse.data) {
                    this.serverRank = serverResponse.data;
                }
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
                this.$root.showToast('Failed to load dashboard data', 'error');
            }
        },
        
        formatMoney(cents) {
            return ((cents || 0) / 100).toFixed(2);
        },
        
        getPercentage(value1, value2) {
            const max = Math.max(value1 || 0, value2 || 0);
            if (max === 0) return 0;
            return Math.round(((value1 || 0) / max) * 100);
        },
        
        renderIncomeChart() {
            const ctx = document.getElementById('incomeChart');
            if (!ctx) return;
            
            // Group data by date
            const incomeByDate = {};
            this.orderStats.forEach(item => {
                if (item.type === '收款金额') {
                    incomeByDate[item.date] = item.value;
                }
            });
            
            const labels = Object.keys(incomeByDate).slice(-14);
            const data = labels.map(label => incomeByDate[label] || 0);
            
            if (this.incomeChart) {
                this.incomeChart.destroy();
            }
            
            this.incomeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Income',
                        data: data,
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    }
}
</script>
@endpush
