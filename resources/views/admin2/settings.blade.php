@extends('admin2.layout')

@section('title', 'Settings')
@section('page_title', 'System Settings')

@section('content')
<div x-data="settingsPage()" x-init="loadSettings()">
    <!-- Settings Tabs -->
    <div class="mb-6">
        <nav class="flex space-x-4 border-b border-gray-200 pb-4">
            <button @click="activeTab = 'site'" 
                    :class="activeTab === 'site' ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 border-transparent'"
                    class="px-4 py-2 text-sm font-medium border-b-2 hover:text-gray-700">
                Site
            </button>
            <button @click="activeTab = 'subscribe'" 
                    :class="activeTab === 'subscribe' ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 border-transparent'"
                    class="px-4 py-2 text-sm font-medium border-b-2 hover:text-gray-700">
                Subscribe
            </button>
            <button @click="activeTab = 'email'" 
                    :class="activeTab === 'email' ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 border-transparent'"
                    class="px-4 py-2 text-sm font-medium border-b-2 hover:text-gray-700">
                Email
            </button>
            <button @click="activeTab = 'telegram'" 
                    :class="activeTab === 'telegram' ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 border-transparent'"
                    class="px-4 py-2 text-sm font-medium border-b-2 hover:text-gray-700">
                Telegram
            </button>
            <button @click="activeTab = 'invite'" 
                    :class="activeTab === 'invite' ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 border-transparent'"
                    class="px-4 py-2 text-sm font-medium border-b-2 hover:text-gray-700">
                Invite
            </button>
            <button @click="activeTab = 'safe'" 
                    :class="activeTab === 'safe' ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 border-transparent'"
                    class="px-4 py-2 text-sm font-medium border-b-2 hover:text-gray-700">
                Security
            </button>
        </nav>
    </div>
    
    <!-- Site Settings -->
    <div x-show="activeTab === 'site'" class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Site Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
                <input type="text" x-model="settings.site.app_name" class="input-field" placeholder="V2Board">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Site URL</label>
                <input type="url" x-model="settings.site.app_url" class="input-field" placeholder="https://example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subscribe URL (optional)</label>
                <input type="url" x-model="settings.site.subscribe_url" class="input-field" placeholder="https://sub.example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Logo URL</label>
                <input type="url" x-model="settings.site.logo" class="input-field" placeholder="https://example.com/logo.png">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Site Description</label>
                <textarea x-model="settings.site.app_description" class="input-field" rows="2"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                <select x-model="settings.site.currency" class="input-field">
                    <option value="CNY">CNY (¥)</option>
                    <option value="USD">USD ($)</option>
                    <option value="EUR">EUR (€)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Currency Symbol</label>
                <input type="text" x-model="settings.site.currency_symbol" class="input-field" placeholder="¥">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.site.stop_register" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Stop new registrations</span>
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.site.force_https" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Force HTTPS</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Subscribe Settings -->
    <div x-show="activeTab === 'subscribe'" x-cloak class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Subscribe Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.subscribe.plan_change_enable" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Allow plan changes</span>
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.subscribe.surplus_enable" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Enable surplus calculation</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Traffic Reset Method</label>
                <select x-model="settings.subscribe.reset_traffic_method" class="input-field">
                    <option value="0">Monthly (1st of month)</option>
                    <option value="1">Monthly (subscription start date)</option>
                    <option value="2">Never reset</option>
                    <option value="3">First day of month (user expires)</option>
                    <option value="4">Reset on subscription renewal</option>
                </select>
            </div>
        </div>
    </div>
    
    <!-- Email Settings -->
    <div x-show="activeTab === 'email'" x-cloak class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Email Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Host</label>
                <input type="text" x-model="settings.email.email_host" class="input-field" placeholder="smtp.example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SMTP Port</label>
                <input type="number" x-model="settings.email.email_port" class="input-field" placeholder="465">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input type="text" x-model="settings.email.email_username" class="input-field" placeholder="noreply@example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input type="password" x-model="settings.email.email_password" class="input-field" placeholder="••••••••">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                <select x-model="settings.email.email_encryption" class="input-field">
                    <option value="ssl">SSL</option>
                    <option value="tls">TLS</option>
                    <option value="">None</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Address</label>
                <input type="email" x-model="settings.email.email_from_address" class="input-field" placeholder="noreply@example.com">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Template</label>
                <select x-model="settings.email.email_template" class="input-field">
                    <template x-for="tpl in emailTemplates" :key="tpl">
                        <option :value="tpl" x-text="tpl"></option>
                    </template>
                </select>
            </div>
            <div>
                <button @click="testEmail()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    Send Test Email
                </button>
            </div>
        </div>
    </div>
    
    <!-- Telegram Settings -->
    <div x-show="activeTab === 'telegram'" x-cloak class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Telegram Bot Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.telegram.telegram_bot_enable" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Enable Telegram Bot</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bot Token</label>
                <input type="text" x-model="settings.telegram.telegram_bot_token" class="input-field" placeholder="123456789:ABC...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Discussion Group Link</label>
                <input type="url" x-model="settings.telegram.telegram_discuss_link" class="input-field" placeholder="https://t.me/yourgroup">
            </div>
            <div class="md:col-span-2">
                <button @click="setWebhook()" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    Set Webhook
                </button>
            </div>
        </div>
    </div>
    
    <!-- Invite Settings -->
    <div x-show="activeTab === 'invite'" x-cloak class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Invite & Commission Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.invite.invite_force" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Force invite code for registration</span>
                </label>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Commission Rate (%)</label>
                <input type="number" x-model="settings.invite.invite_commission" class="input-field" placeholder="10">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Max Invite Codes per User</label>
                <input type="number" x-model="settings.invite.invite_gen_limit" class="input-field" placeholder="5">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Min Withdrawal Amount</label>
                <input type="number" x-model="settings.invite.commission_withdraw_limit" class="input-field" placeholder="100">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.invite.invite_never_expire" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Invite codes never expire</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Security Settings -->
    <div x-show="activeTab === 'safe'" x-cloak class="card p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Security Settings</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Admin Panel Path</label>
                <input type="text" x-model="settings.safe.secure_path" class="input-field" placeholder="admin">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.safe.email_verify" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Require email verification</span>
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.safe.safe_mode_enable" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Enable safe mode (restrict access by domain)</span>
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.safe.recaptcha_enable" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Enable reCAPTCHA</span>
                </label>
            </div>
            <div x-show="settings.safe.recaptcha_enable">
                <label class="block text-sm font-medium text-gray-700 mb-1">reCAPTCHA Site Key</label>
                <input type="text" x-model="settings.safe.recaptcha_site_key" class="input-field">
            </div>
            <div x-show="settings.safe.recaptcha_enable">
                <label class="block text-sm font-medium text-gray-700 mb-1">reCAPTCHA Secret Key</label>
                <input type="text" x-model="settings.safe.recaptcha_key" class="input-field">
            </div>
            <div class="md:col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" x-model="settings.safe.register_limit_by_ip_enable" class="rounded border-gray-300 text-indigo-600">
                    <span class="ml-2 text-sm text-gray-700">Limit registrations by IP</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Save Button -->
    <div class="mt-6 flex justify-end">
        <button @click="saveSettings()" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors font-medium">
            Save All Settings
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function settingsPage() {
    return {
        activeTab: 'site',
        settings: {
            site: {},
            subscribe: {},
            email: {},
            telegram: {},
            invite: {},
            safe: {}
        },
        emailTemplates: [],
        
        async loadSettings() {
            try {
                this.$root.loading = true;
                const response = await this.$root.api('/admin/config/fetch');
                if (response.data) {
                    this.settings = response.data;
                }
                
                // Load email templates
                const templatesResponse = await this.$root.api('/admin/config/getEmailTemplate');
                this.emailTemplates = templatesResponse.data || [];
            } catch (error) {
                this.$root.showToast('Failed to load settings', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async saveSettings() {
            try {
                this.$root.loading = true;
                
                // Flatten settings
                const data = {};
                Object.keys(this.settings).forEach(category => {
                    Object.keys(this.settings[category]).forEach(key => {
                        data[key] = this.settings[category][key];
                    });
                });
                
                await this.$root.api('/admin/config/save', 'POST', data);
                this.$root.showToast('Settings saved successfully', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Save failed', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async testEmail() {
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/config/testSendMail', 'POST');
                this.$root.showToast('Test email sent', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Failed to send test email', 'error');
            } finally {
                this.$root.loading = false;
            }
        },
        
        async setWebhook() {
            try {
                this.$root.loading = true;
                await this.$root.api('/admin/config/setTelegramWebhook', 'POST', {
                    telegram_bot_token: this.settings.telegram.telegram_bot_token
                });
                this.$root.showToast('Webhook set successfully', 'success');
            } catch (error) {
                this.$root.showToast(error.message || 'Failed to set webhook', 'error');
            } finally {
                this.$root.loading = false;
            }
        }
    }
}
</script>
@endpush
