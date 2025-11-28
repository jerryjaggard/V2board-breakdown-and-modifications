<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plugin;
use App\Services\Plugin\PluginManager;
use App\Services\Plugin\PluginConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PluginController extends Controller
{
    protected PluginManager $pluginManager;
    protected PluginConfigService $configService;

    public function __construct(
        PluginManager $pluginManager,
        PluginConfigService $configService
    ) {
        $this->pluginManager = $pluginManager;
        $this->configService = $configService;
    }

    /**
     * Get all plugin types
     */
    public function types()
    {
        return response([
            'data' => [
                [
                    'value' => Plugin::TYPE_FEATURE,
                    'label' => 'Feature',
                    'description' => 'Plugins that provide feature extensions, such as Telegram login, email notifications, etc.',
                    'icon' => '🔧'
                ],
                [
                    'value' => Plugin::TYPE_PAYMENT,
                    'label' => 'Payment',
                    'description' => 'Plugins that provide payment interfaces, such as Alipay, WeChat Pay, etc.',
                    'icon' => '💳'
                ]
            ]
        ]);
    }

    /**
     * Get plugin list
     */
    public function index(Request $request)
    {
        $type = $request->query('type');

        try {
            $installedPlugins = Plugin::when($type, function ($query) use ($type) {
                return $query->byType($type);
            })
                ->get()
                ->keyBy('code')
                ->toArray();
        } catch (\Exception $e) {
            $installedPlugins = [];
        }

        $pluginPath = base_path('plugins');
        $plugins = [];

        if (File::exists($pluginPath)) {
            $directories = File::directories($pluginPath);
            foreach ($directories as $directory) {
                $pluginName = basename($directory);
                $configFile = $directory . '/config.json';
                if (File::exists($configFile)) {
                    $config = json_decode(File::get($configFile), true);
                    if (!$config || !isset($config['code'])) {
                        continue;
                    }
                    $code = $config['code'];
                    $pluginType = $config['type'] ?? Plugin::TYPE_FEATURE;

                    // Filter by type if specified
                    if ($type && $pluginType !== $type) {
                        continue;
                    }

                    $installed = isset($installedPlugins[$code]);
                    $pluginConfig = $installed ? $this->configService->getConfig($code) : ($config['config'] ?? []);
                    
                    $readmeFile = collect(['README.md', 'readme.md'])
                        ->map(fn($f) => $directory . '/' . $f)
                        ->first(fn($path) => File::exists($path));
                    $readmeContent = $readmeFile ? File::get($readmeFile) : '';
                    
                    $needUpgrade = false;
                    if ($installed) {
                        $installedVersion = $installedPlugins[$code]['version'] ?? null;
                        $localVersion = $config['version'] ?? null;
                        if ($installedVersion && $localVersion && version_compare($localVersion, $installedVersion, '>')) {
                            $needUpgrade = true;
                        }
                    }
                    
                    $plugins[] = [
                        'code' => $config['code'],
                        'name' => $config['name'],
                        'version' => $config['version'],
                        'description' => $config['description'],
                        'author' => $config['author'],
                        'type' => $pluginType,
                        'is_installed' => $installed,
                        'is_enabled' => $installed ? (bool)$installedPlugins[$code]['is_enabled'] : false,
                        'is_protected' => in_array($code, Plugin::PROTECTED_PLUGINS),
                        'can_be_deleted' => !in_array($code, Plugin::PROTECTED_PLUGINS),
                        'config' => $pluginConfig,
                        'readme' => $readmeContent,
                        'need_upgrade' => $needUpgrade,
                    ];
                }
            }
        }

        return response([
            'data' => $plugins
        ]);
    }

    /**
     * Install plugin
     */
    public function install(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        try {
            $this->pluginManager->install($request->input('code'));
            return response([
                'data' => true,
                'message' => 'Plugin installed successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Plugin installation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Uninstall plugin
     */
    public function uninstall(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->input('code');
        
        try {
            $plugin = Plugin::where('code', $code)->first();
            if ($plugin && $plugin->is_enabled) {
                return response([
                    'message' => 'Please disable the plugin before uninstalling'
                ], 400);
            }

            $this->pluginManager->uninstall($code);
            return response([
                'data' => true,
                'message' => 'Plugin uninstalled successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Plugin uninstallation failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Upgrade plugin
     */
    public function upgrade(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);
        
        try {
            $this->pluginManager->update($request->input('code'));
            return response([
                'data' => true,
                'message' => 'Plugin upgraded successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Plugin upgrade failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Enable plugin
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        try {
            $this->pluginManager->enable($request->input('code'));
            return response([
                'data' => true,
                'message' => 'Plugin enabled successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Plugin enable failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Disable plugin
     */
    public function disable(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        try {
            $this->pluginManager->disable($request->input('code'));
            return response([
                'data' => true,
                'message' => 'Plugin disabled successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Plugin disable failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get plugin configuration
     */
    public function getConfig(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        try {
            $config = $this->configService->getConfig($request->input('code'));
            return response([
                'data' => $config
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Failed to get configuration: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update plugin configuration
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'config' => 'required|array'
        ]);

        try {
            $this->configService->updateConfig(
                $request->input('code'),
                $request->input('config')
            );

            return response([
                'data' => true,
                'message' => 'Configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Configuration update failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Upload plugin
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:zip',
                'max:10240', // Max 10MB
            ]
        ], [
            'file.required' => 'Please select a plugin package file',
            'file.file' => 'Invalid file type',
            'file.mimes' => 'Plugin package must be in zip format',
            'file.max' => 'Plugin package size cannot exceed 10MB'
        ]);

        try {
            $this->pluginManager->upload($request->file('file'));
            return response([
                'data' => true,
                'message' => 'Plugin uploaded successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Plugin upload failed: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Delete plugin
     */
    public function delete(Request $request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $code = $request->input('code');

        // Check if protected plugin
        if (in_array($code, Plugin::PROTECTED_PLUGINS)) {
            return response([
                'message' => 'This plugin is a system default plugin and cannot be deleted'
            ], 403);
        }

        try {
            $this->pluginManager->delete($code);
            return response([
                'data' => true,
                'message' => 'Plugin deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response([
                'message' => 'Plugin deletion failed: ' . $e->getMessage()
            ], 400);
        }
    }
}
