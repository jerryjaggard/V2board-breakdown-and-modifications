<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$title}} - Plugin Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        .header a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 6px;
            transition: background 0.3s;
        }
        .header a:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-card h3 {
            color: #8c8c8c;
            font-size: 14px;
            margin-bottom: 8px;
        }
        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: #262626;
        }
        .section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .section-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-header h2 {
            font-size: 18px;
            font-weight: 600;
            color: #262626;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-primary {
            background: #1890ff;
            color: white;
        }
        .btn-primary:hover {
            background: #40a9ff;
        }
        .btn-success {
            background: #52c41a;
            color: white;
        }
        .btn-success:hover {
            background: #73d13d;
        }
        .btn-warning {
            background: #faad14;
            color: white;
        }
        .btn-warning:hover {
            background: #ffc53d;
        }
        .btn-danger {
            background: #ff4d4f;
            color: white;
        }
        .btn-danger:hover {
            background: #ff7875;
        }
        .btn-secondary {
            background: #f0f0f0;
            color: #595959;
        }
        .btn-secondary:hover {
            background: #d9d9d9;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .plugins-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            padding: 24px;
        }
        .plugin-card {
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
        }
        .plugin-card:hover {
            border-color: #1890ff;
            box-shadow: 0 4px 12px rgba(24, 144, 255, 0.15);
        }
        .plugin-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .plugin-title {
            font-size: 18px;
            font-weight: 600;
            color: #262626;
        }
        .plugin-version {
            font-size: 12px;
            color: #8c8c8c;
            background: #f5f5f5;
            padding: 2px 8px;
            border-radius: 4px;
        }
        .plugin-author {
            font-size: 13px;
            color: #8c8c8c;
            margin-bottom: 8px;
        }
        .plugin-description {
            font-size: 14px;
            color: #595959;
            line-height: 1.6;
            margin-bottom: 16px;
        }
        .plugin-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 16px;
        }
        .type-feature {
            background: #e6f7ff;
            color: #1890ff;
        }
        .type-payment {
            background: #fff7e6;
            color: #fa8c16;
        }
        .plugin-status {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        .status-enabled {
            background: #52c41a;
        }
        .status-disabled {
            background: #d9d9d9;
        }
        .status-not-installed {
            background: #faad14;
        }
        .plugin-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .loading {
            text-align: center;
            padding: 60px;
            color: #8c8c8c;
        }
        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f0f0f0;
            border-top-color: #1890ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .empty {
            text-align: center;
            padding: 60px;
            color: #8c8c8c;
        }
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal {
            background: white;
            border-radius: 12px;
            padding: 24px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal h3 {
            margin-bottom: 20px;
            font-size: 20px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #262626;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #1890ff;
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        }
        .toast-success {
            background: #52c41a;
        }
        .toast-error {
            background: #ff4d4f;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .hidden {
            display: none !important;
        }
        .tabs {
            display: flex;
            gap: 4px;
            padding: 16px 24px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .tab {
            padding: 12px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 14px;
            color: #595959;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
        }
        .tab:hover {
            color: #1890ff;
        }
        .tab.active {
            color: #1890ff;
            border-bottom-color: #1890ff;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🔌 Plugin Management</h1>
        <a href="/{{$secure_path}}">← Back to Admin Panel</a>
    </header>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3>Total Plugins</h3>
                <div class="value" id="stat-total">-</div>
            </div>
            <div class="stat-card">
                <h3>Enabled</h3>
                <div class="value" id="stat-enabled">-</div>
            </div>
            <div class="stat-card">
                <h3>Feature Plugins</h3>
                <div class="value" id="stat-feature">-</div>
            </div>
            <div class="stat-card">
                <h3>Payment Plugins</h3>
                <div class="value" id="stat-payment">-</div>
            </div>
        </div>

        <div class="section">
            <div class="section-header">
                <h2>Plugins</h2>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-secondary" onclick="loadPlugins()">↻ Refresh</button>
                    <label class="btn btn-primary" style="cursor: pointer;">
                        📤 Upload Plugin
                        <input type="file" accept=".zip" onchange="uploadPlugin(this)" style="display: none;">
                    </label>
                </div>
            </div>
            
            <div class="tabs">
                <button class="tab active" data-type="">All</button>
                <button class="tab" data-type="feature">Feature</button>
                <button class="tab" data-type="payment">Payment</button>
            </div>
            
            <div id="plugins-container" class="plugins-grid">
                <div class="loading">Loading plugins...</div>
            </div>
        </div>
    </div>

    <!-- Config Modal -->
    <div id="config-modal" class="modal-overlay hidden">
        <div class="modal">
            <h3>Plugin Configuration</h3>
            <div id="config-form"></div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeConfigModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveConfig()">Save Configuration</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast" class="toast hidden"></div>

    <script>
        const API_BASE = '/api/v1/{{$secure_path}}';
        let plugins = [];
        let currentPlugin = null;
        let currentType = '';

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadPlugins();
            
            // Tab handlers
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    currentType = tab.dataset.type;
                    renderPlugins();
                });
            });
        });

        async function apiCall(endpoint, method = 'GET', data = null) {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': localStorage.getItem('auth_data') || ''
                }
            };
            if (data) {
                options.body = JSON.stringify(data);
            }
            const response = await fetch(API_BASE + endpoint, options);
            const result = await response.json();
            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }
            return result;
        }

        async function loadPlugins() {
            try {
                const container = document.getElementById('plugins-container');
                container.innerHTML = '<div class="loading">Loading plugins...</div>';
                
                const result = await apiCall('/plugin/fetch');
                plugins = result.data || [];
                
                updateStats();
                renderPlugins();
            } catch (error) {
                showToast(error.message, 'error');
                document.getElementById('plugins-container').innerHTML = 
                    '<div class="empty">Failed to load plugins. Please check your authentication.</div>';
            }
        }

        function updateStats() {
            const enabled = plugins.filter(p => p.is_enabled).length;
            const feature = plugins.filter(p => p.type === 'feature').length;
            const payment = plugins.filter(p => p.type === 'payment').length;
            
            document.getElementById('stat-total').textContent = plugins.length;
            document.getElementById('stat-enabled').textContent = enabled;
            document.getElementById('stat-feature').textContent = feature;
            document.getElementById('stat-payment').textContent = payment;
        }

        function renderPlugins() {
            const container = document.getElementById('plugins-container');
            const filtered = currentType 
                ? plugins.filter(p => p.type === currentType)
                : plugins;
            
            if (filtered.length === 0) {
                container.innerHTML = '<div class="empty">No plugins found</div>';
                return;
            }

            container.innerHTML = filtered.map(plugin => `
                <div class="plugin-card">
                    <div class="plugin-header">
                        <div class="plugin-title">${escapeHtml(plugin.name)}</div>
                        <div class="plugin-version">v${escapeHtml(plugin.version)}</div>
                    </div>
                    <div class="plugin-author">by ${escapeHtml(plugin.author || 'Unknown')}</div>
                    <div class="plugin-type type-${plugin.type}">${plugin.type === 'payment' ? '💳 Payment' : '🔧 Feature'}</div>
                    <div class="plugin-description">${escapeHtml(plugin.description || 'No description')}</div>
                    <div class="plugin-status">
                        <span class="status-dot ${plugin.is_installed ? (plugin.is_enabled ? 'status-enabled' : 'status-disabled') : 'status-not-installed'}"></span>
                        <span>${plugin.is_installed ? (plugin.is_enabled ? 'Enabled' : 'Disabled') : 'Not Installed'}</span>
                    </div>
                    <div class="plugin-actions">
                        ${getPluginActions(plugin)}
                    </div>
                </div>
            `).join('');
        }

        function getPluginActions(plugin) {
            let actions = [];
            
            if (!plugin.is_installed) {
                actions.push(`<button class="btn btn-primary" onclick="installPlugin('${plugin.code}')">Install</button>`);
            } else {
                if (plugin.is_enabled) {
                    actions.push(`<button class="btn btn-warning" onclick="disablePlugin('${plugin.code}')">Disable</button>`);
                } else {
                    actions.push(`<button class="btn btn-success" onclick="enablePlugin('${plugin.code}')">Enable</button>`);
                }
                actions.push(`<button class="btn btn-secondary" onclick="openConfigModal('${plugin.code}')">⚙️ Config</button>`);
                if (!plugin.is_enabled) {
                    actions.push(`<button class="btn btn-secondary" onclick="uninstallPlugin('${plugin.code}')">Uninstall</button>`);
                }
                if (plugin.need_upgrade) {
                    actions.push(`<button class="btn btn-primary" onclick="upgradePlugin('${plugin.code}')">⬆️ Upgrade</button>`);
                }
            }
            
            if (!plugin.is_protected && !plugin.is_installed) {
                actions.push(`<button class="btn btn-danger" onclick="deletePlugin('${plugin.code}')">🗑️ Delete</button>`);
            }
            
            return actions.join('');
        }

        async function installPlugin(code) {
            try {
                await apiCall('/plugin/install', 'POST', { code });
                showToast('Plugin installed successfully', 'success');
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function uninstallPlugin(code) {
            if (!confirm('Are you sure you want to uninstall this plugin?')) return;
            try {
                await apiCall('/plugin/uninstall', 'POST', { code });
                showToast('Plugin uninstalled successfully', 'success');
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function enablePlugin(code) {
            try {
                await apiCall('/plugin/enable', 'POST', { code });
                showToast('Plugin enabled successfully', 'success');
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function disablePlugin(code) {
            try {
                await apiCall('/plugin/disable', 'POST', { code });
                showToast('Plugin disabled successfully', 'success');
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function upgradePlugin(code) {
            try {
                await apiCall('/plugin/upgrade', 'POST', { code });
                showToast('Plugin upgraded successfully', 'success');
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function deletePlugin(code) {
            if (!confirm('Are you sure you want to delete this plugin? This cannot be undone.')) return;
            try {
                await apiCall('/plugin/delete', 'POST', { code });
                showToast('Plugin deleted successfully', 'success');
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function openConfigModal(code) {
            currentPlugin = plugins.find(p => p.code === code);
            if (!currentPlugin) return;
            
            try {
                const result = await apiCall('/plugin/getConfig', 'POST', { code });
                const config = result.data || {};
                
                const formHtml = Object.entries(currentPlugin.config || {}).map(([key, field]) => {
                    const value = config[key] !== undefined ? config[key] : (field.default || '');
                    return `
                        <div class="form-group">
                            <label>${escapeHtml(field.label || key)}</label>
                            ${getFormInput(key, field, value)}
                            ${field.description ? `<small style="color: #8c8c8c;">${escapeHtml(field.description)}</small>` : ''}
                        </div>
                    `;
                }).join('');
                
                document.getElementById('config-form').innerHTML = formHtml || '<p>No configuration options available.</p>';
                document.getElementById('config-modal').classList.remove('hidden');
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        function getFormInput(key, field, value) {
            const type = field.type || 'text';
            
            if (type === 'select' && field.options) {
                const options = Object.entries(field.options)
                    .map(([k, v]) => `<option value="${escapeHtml(k)}" ${value == k ? 'selected' : ''}>${escapeHtml(v)}</option>`)
                    .join('');
                return `<select name="${escapeHtml(key)}">${options}</select>`;
            }
            
            if (type === 'textarea') {
                return `<textarea name="${escapeHtml(key)}" rows="4">${escapeHtml(value)}</textarea>`;
            }
            
            if (type === 'boolean') {
                return `<select name="${escapeHtml(key)}">
                    <option value="1" ${value ? 'selected' : ''}>Yes</option>
                    <option value="0" ${!value ? 'selected' : ''}>No</option>
                </select>`;
            }
            
            return `<input type="${type === 'number' ? 'number' : 'text'}" name="${escapeHtml(key)}" value="${escapeHtml(value)}">`;
        }

        function closeConfigModal() {
            document.getElementById('config-modal').classList.add('hidden');
            currentPlugin = null;
        }

        async function saveConfig() {
            if (!currentPlugin) return;
            
            const form = document.getElementById('config-form');
            const inputs = form.querySelectorAll('input, select, textarea');
            const config = {};
            
            inputs.forEach(input => {
                let value = input.value;
                if (input.type === 'number') {
                    value = parseFloat(value) || 0;
                }
                config[input.name] = value;
            });
            
            try {
                await apiCall('/plugin/updateConfig', 'POST', {
                    code: currentPlugin.code,
                    config
                });
                showToast('Configuration saved successfully', 'success');
                closeConfigModal();
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        async function uploadPlugin(input) {
            const file = input.files[0];
            if (!file) return;
            
            const formData = new FormData();
            formData.append('file', file);
            
            try {
                const response = await fetch(API_BASE + '/plugin/upload', {
                    method: 'POST',
                    headers: {
                        'Authorization': localStorage.getItem('auth_data') || ''
                    },
                    body: formData
                });
                
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Upload failed');
                }
                
                showToast('Plugin uploaded successfully', 'success');
                loadPlugins();
            } catch (error) {
                showToast(error.message, 'error');
            }
            
            input.value = '';
        }

        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast toast-${type}`;
            toast.classList.remove('hidden');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 3000);
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    </script>
</body>
</html>
