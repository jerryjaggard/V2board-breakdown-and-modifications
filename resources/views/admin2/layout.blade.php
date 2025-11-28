<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') - {{ config('v2board.app_name', 'V2Board') }}</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                        secondary: '#8b5cf6',
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link.active { background-color: rgba(99, 102, 241, 0.1); color: #6366f1; border-right: 3px solid #6366f1; }
        .sidebar-link:hover { background-color: rgba(99, 102, 241, 0.05); }
        .stats-card { transition: transform 0.2s, box-shadow 0.2s; }
        .stats-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .table-row:hover { background-color: #f9fafb; }
        .btn-primary { @apply bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors; }
        .btn-secondary { @apply bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors; }
        .input-field { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all; }
        .card { @apply bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden; }
        .badge { @apply px-2 py-1 text-xs font-medium rounded-full; }
        .badge-success { @apply bg-green-100 text-green-800; }
        .badge-warning { @apply bg-yellow-100 text-yellow-800; }
        .badge-danger { @apply bg-red-100 text-red-800; }
        .badge-info { @apply bg-blue-100 text-blue-800; }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #6366f1;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    @stack('styles')
</head>
<body class="h-full" x-data="adminApp()" x-init="init()">
    <div class="min-h-full">
        <!-- Mobile sidebar backdrop -->
        <div x-show="sidebarOpen" x-cloak
             class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
             @click="sidebarOpen = false"></div>
        
        <!-- Sidebar -->
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
            
            <!-- Logo -->
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-200">
                <a href="/{{ $secure_path }}/admin2" class="flex items-center space-x-2">
                    @if(config('v2board.logo'))
                        <img src="{{ config('v2board.logo') }}" alt="Logo" class="h-8 w-8">
                    @else
                        <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">V2</span>
                        </div>
                    @endif
                    <span class="font-semibold text-gray-900">{{ config('v2board.app_name', 'V2Board') }}</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <!-- Dashboard -->
                <a href="/{{ $secure_path }}/admin2" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>
                
                <!-- Users -->
                <a href="/{{ $secure_path }}/admin2/users" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/users*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Users
                </a>
                
                <!-- Orders -->
                <a href="/{{ $secure_path }}/admin2/orders" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/orders*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    Orders
                </a>
                
                <!-- Plans -->
                <a href="/{{ $secure_path }}/admin2/plans" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/plans*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Plans
                </a>
                
                <!-- Servers -->
                <a href="/{{ $secure_path }}/admin2/servers" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/servers*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                    </svg>
                    Servers
                </a>
                
                <!-- Tickets -->
                <a href="/{{ $secure_path }}/admin2/tickets" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/tickets*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    Tickets
                    <span x-show="pendingTickets > 0" x-text="pendingTickets" 
                          class="ml-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full"></span>
                </a>
                
                <!-- Payments -->
                <a href="/{{ $secure_path }}/admin2/payments" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/payments*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    Payments
                </a>
                
                <!-- Coupons -->
                <a href="/{{ $secure_path }}/admin2/coupons" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/coupons*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    Coupons
                </a>
                
                <!-- Knowledge Base -->
                <a href="/{{ $secure_path }}/admin2/knowledge" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/knowledge*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    Knowledge
                </a>
                
                <!-- Notices -->
                <a href="/{{ $secure_path }}/admin2/notices" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/notices*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
                    </svg>
                    Notices
                </a>
                
                <div class="pt-4 mt-4 border-t border-gray-200">
                    <p class="px-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</p>
                </div>
                
                <!-- Plugins -->
                <a href="/{{ $secure_path }}/admin2/plugins" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/plugins*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                    Plugins
                    <span class="ml-auto bg-indigo-100 text-indigo-800 text-xs px-2 py-0.5 rounded-full">New</span>
                </a>
                
                <!-- Settings -->
                <a href="/{{ $secure_path }}/admin2/settings" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/settings*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </a>
                
                <!-- System Info -->
                <a href="/{{ $secure_path }}/admin2/system" 
                   class="sidebar-link flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-lg {{ request()->is($secure_path.'/admin2/system*') ? 'active' : '' }}">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    System
                </a>
            </nav>
            
            <!-- User Info -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                        <span class="text-white text-sm font-medium" x-text="adminEmail ? adminEmail.charAt(0).toUpperCase() : 'A'"></span>
                    </div>
                    <div class="ml-3 flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate" x-text="adminEmail || 'Admin'"></p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                    <button @click="logout()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </div>
            </div>
        </aside>
        
        <!-- Main content -->
        <div class="lg:pl-64 flex flex-col min-h-screen">
            <!-- Top bar -->
            <header class="sticky top-0 z-30 bg-white border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    
                    <div class="flex-1 flex items-center justify-between">
                        <h1 class="text-lg font-semibold text-gray-900 ml-4 lg:ml-0">@yield('page_title', 'Dashboard')</h1>
                        
                        <div class="flex items-center space-x-4">
                            <!-- Quick Actions -->
                            <a href="/" target="_blank" class="text-gray-400 hover:text-gray-600" title="Visit Site">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                            </a>
                            
                            <!-- Notifications -->
                            <button class="relative text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span x-show="pendingTickets > 0" class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
            
            <!-- Footer -->
            <footer class="bg-white border-t border-gray-200 px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex flex-col sm:flex-row items-center justify-between text-sm text-gray-500">
                    <p>{{ config('v2board.app_name', 'V2Board') }} v{{ config('app.version', '1.0.0') }}</p>
                    <p class="mt-2 sm:mt-0">New Admin Panel with Plugin Support</p>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Toast Notifications -->
    <div x-show="toast.show" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="fixed bottom-4 right-4 z-50">
        <div :class="{
            'bg-green-500': toast.type === 'success',
            'bg-red-500': toast.type === 'error',
            'bg-yellow-500': toast.type === 'warning',
            'bg-blue-500': toast.type === 'info'
        }" class="px-6 py-3 rounded-lg shadow-lg text-white font-medium">
            <span x-text="toast.message"></span>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div x-show="loading" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
        <div class="bg-white rounded-lg p-6 shadow-xl">
            <div class="loading-spinner mx-auto"></div>
            <p class="mt-3 text-gray-600 text-sm">Loading...</p>
        </div>
    </div>
    
    <script>
        // Global admin app
        function adminApp() {
            return {
                sidebarOpen: false,
                loading: false,
                adminEmail: '',
                pendingTickets: 0,
                authToken: '',
                authError: false,
                toast: {
                    show: false,
                    message: '',
                    type: 'success'
                },
                
                init() {
                    // Get auth data from multiple possible localStorage keys
                    // V2Board old admin stores auth in different ways
                    this.authToken = this.getAuthToken();
                    
                    if (!this.authToken) {
                        // Redirect to login
                        window.location.href = '/{{ $secure_path }}/admin2/login';
                        return;
                    }
                    
                    // Load pending tickets count and verify auth
                    this.loadStats();
                },
                
                getAuthToken() {
                    // Try multiple storage keys and formats
                    const keys = ['auth_data', 'admin_auth', 'token', 'Authorization'];
                    
                    for (const key of keys) {
                        const value = localStorage.getItem(key);
                        if (value) {
                            try {
                                const parsed = JSON.parse(value);
                                if (parsed.auth_data) {
                                    this.adminEmail = parsed.email || '';
                                    return parsed.auth_data;
                                }
                                if (parsed.token) {
                                    this.adminEmail = parsed.email || '';
                                    return parsed.token;
                                }
                                if (parsed.data && parsed.data.auth_data) {
                                    this.adminEmail = parsed.data.email || '';
                                    return parsed.data.auth_data;
                                }
                            } catch (e) {
                                // Not JSON, might be raw token
                                if (value.length > 20) {
                                    return value;
                                }
                            }
                        }
                    }
                    
                    // Check sessionStorage as well
                    for (const key of keys) {
                        const value = sessionStorage.getItem(key);
                        if (value) {
                            try {
                                const parsed = JSON.parse(value);
                                if (parsed.auth_data) return parsed.auth_data;
                                if (parsed.token) return parsed.token;
                            } catch (e) {
                                if (value.length > 20) return value;
                            }
                        }
                    }
                    
                    return '';
                },
                
                async loadStats() {
                    try {
                        const response = await this.api('/admin/stat/getOverride');
                        if (response.data) {
                            this.pendingTickets = response.data.ticket_pending_total || 0;
                            this.authError = false;
                        }
                    } catch (e) {
                        console.error('Failed to load stats:', e);
                        if (e.message === 'Unauthorized' || e.message.includes('鉴权') || e.status === 401 || e.status === 403) {
                            this.authError = true;
                            // Clear potentially invalid auth
                            localStorage.removeItem('auth_data');
                            localStorage.removeItem('admin_auth');
                            window.location.href = '/{{ $secure_path }}/admin2/login';
                        }
                    }
                },
                
                async api(endpoint, method = 'GET', data = null) {
                    const url = '/api/v1' + endpoint;
                    const options = {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': this.authToken
                        }
                    };
                    
                    if (data && method !== 'GET') {
                        options.body = JSON.stringify(data);
                    }
                    
                    const response = await fetch(url, options);
                    const result = await response.json();
                    
                    if (!response.ok) {
                        const error = new Error(result.message || 'API Error');
                        error.status = response.status;
                        
                        // Handle auth errors
                        if (response.status === 401 || response.status === 403) {
                            localStorage.removeItem('auth_data');
                            localStorage.removeItem('admin_auth');
                            window.location.href = '/{{ $secure_path }}/admin2/login';
                            throw error;
                        }
                        
                        throw error;
                    }
                    
                    return result;
                },
                
                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                },
                
                logout() {
                    localStorage.removeItem('auth_data');
                    localStorage.removeItem('admin_auth');
                    window.location.href = '/{{ $secure_path }}';
                },
                
                formatBytes(bytes) {
                    if (bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                },
                
                formatDate(timestamp) {
                    if (!timestamp) return '-';
                    const date = new Date(timestamp * 1000);
                    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
                },
                
                formatMoney(cents) {
                    return (cents / 100).toFixed(2);
                }
            }
        }
    </script>
    
    @stack('scripts')
</body>
</html>
