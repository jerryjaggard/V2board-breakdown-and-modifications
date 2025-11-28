<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - {{ config('v2board.app_name', 'V2Board') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full flex items-center justify-center">
    <div x-data="loginPage()" class="w-full max-w-md px-4">
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Logo -->
            <div class="text-center mb-8">
                @if(config('v2board.logo'))
                    <img src="{{ config('v2board.logo') }}" alt="Logo" class="h-12 mx-auto mb-4">
                @else
                    <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <span class="text-white font-bold text-2xl">V2</span>
                    </div>
                @endif
                <h1 class="text-2xl font-bold text-gray-900">{{ config('v2board.app_name', 'V2Board') }}</h1>
                <p class="text-gray-500 mt-2">Admin Panel Login</p>
            </div>
            
            <!-- Login Form -->
            <form @submit.prevent="login()">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" x-model="email" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                               placeholder="admin@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" x-model="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all"
                               placeholder="••••••••">
                    </div>
                </div>
                
                <button type="submit" :disabled="loading"
                        class="w-full mt-6 bg-indigo-600 text-white py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">Sign In</span>
                    <span x-show="loading">Signing in...</span>
                </button>
            </form>
            
            <!-- Error Message -->
            <div x-show="error" x-cloak class="mt-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm" x-text="error"></div>
            
            <!-- Links -->
            <div class="mt-6 text-center">
                <a href="/" class="text-sm text-gray-500 hover:text-indigo-600">← Back to site</a>
            </div>
        </div>
        
        <!-- Footer -->
        <p class="text-center text-sm text-gray-400 mt-6">
            New Admin Panel v2.0 with Plugin Support
        </p>
    </div>
    
    <script>
        function loginPage() {
            return {
                email: '',
                password: '',
                loading: false,
                error: '',
                
                async login() {
                    this.loading = true;
                    this.error = '';
                    
                    try {
                        const response = await fetch('/api/v1/passport/auth/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                email: this.email,
                                password: this.password
                            })
                        });
                        
                        const result = await response.json();
                        
                        if (!response.ok) {
                            throw new Error(result.message || 'Login failed');
                        }
                        
                        if (result.data && result.data.is_admin) {
                            // Store auth data in multiple formats for compatibility
                            const authData = {
                                auth_data: result.data.auth_data,
                                email: this.email,
                                is_admin: true,
                                token: result.data.auth_data
                            };
                            
                            // Store in different keys for compatibility with both panels
                            localStorage.setItem('auth_data', JSON.stringify(authData));
                            localStorage.setItem('admin_auth', result.data.auth_data);
                            
                            // Redirect to dashboard
                            window.location.href = '/{{ $secure_path }}/admin2';
                        } else {
                            throw new Error('You are not an administrator');
                        }
                    } catch (e) {
                        this.error = e.message;
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
