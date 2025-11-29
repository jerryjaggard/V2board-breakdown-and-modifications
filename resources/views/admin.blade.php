<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="/assets/admin/components.chunk.css?v={{$version}}">
    <link rel="stylesheet" href="/assets/admin/umi.css?v={{$version}}">
    <link rel="stylesheet" href="/assets/admin/custom.css?v={{$version}}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,minimum-scale=1,user-scalable=no">
    <title>{{$title}}</title>
    <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,400i,600,700"> -->
    <script>window.routerBase = "/";</script>
    <script>
        window.settings = {
            title: '{{$title}}',
            theme: {
                sidebar: '{{$theme_sidebar}}',
                header: '{{$theme_header}}',
                color: '{{$theme_color}}',
            },
            version: '{{$version}}',
            background_url: '{{$background_url}}',
            logo: '{{$logo}}',
            secure_path: '{{$secure_path}}'
        }
        
        // Plugin System: Inject Plugins Link into Admin Sidebar
        document.addEventListener('DOMContentLoaded', function() {
            // Wait for the sidebar to be rendered
            function injectPluginsLink() {
                // Look for the sidebar menu
                const sidebar = document.querySelector('.ant-layout-sider-children') || 
                                document.querySelector('.ant-menu-dark') ||
                                document.querySelector('[class*="sider"]');
                
                if (sidebar) {
                    // Check if already injected
                    if (document.querySelector('.v2b-plugins-link-injected')) return;
                    
                    // Find the menu
                    const menu = sidebar.querySelector('.ant-menu') || sidebar.querySelector('ul');
                    if (menu) {
                        // Create plugins menu item
                        const pluginsItem = document.createElement('li');
                        pluginsItem.className = 'ant-menu-item v2b-plugins-link-injected';
                        pluginsItem.style.cssText = 'padding: 0 !important;';
                        pluginsItem.innerHTML = `
                            <a href="/{{$secure_path}}/plugins" 
                               style="display: flex; align-items: center; padding: 0 24px; height: 40px; line-height: 40px; color: inherit; text-decoration: none;">
                                <span style="margin-right: 10px;">🔌</span>
                                <span>Plugins</span>
                            </a>
                        `;
                        
                        // Try to insert before the last item or at the end
                        const items = menu.querySelectorAll('.ant-menu-item, .ant-menu-submenu');
                        if (items.length > 0) {
                            const lastItem = items[items.length - 1];
                            lastItem.parentNode.insertBefore(pluginsItem, lastItem.nextSibling);
                        } else {
                            menu.appendChild(pluginsItem);
                        }
                    }
                }
            }
            
            // Try immediately and also with delay for React render
            injectPluginsLink();
            setTimeout(injectPluginsLink, 500);
            setTimeout(injectPluginsLink, 1000);
            setTimeout(injectPluginsLink, 2000);
            
            // Also observe DOM changes
            const observer = new MutationObserver(function(mutations) {
                injectPluginsLink();
            });
            observer.observe(document.body, { childList: true, subtree: true });
            
            // Stop observing after 10 seconds to prevent performance issues
            setTimeout(function() {
                observer.disconnect();
            }, 10000);
        });
    </script>
</head>

<body>
<div id="root"></div>
<script src="/assets/admin/vendors.async.js?v={{$version}}"></script>
<script src="/assets/admin/components.async.js?v={{$version}}"></script>
<script src="/assets/admin/umi.js?v={{$version}}"></script>
</body>

</html>
