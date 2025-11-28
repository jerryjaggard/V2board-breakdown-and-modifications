<?php

namespace App\Services\Plugin;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractPlugin
{
    protected array $config = [];
    protected string $basePath;
    protected string $pluginCode;
    protected string $namespace;

    public function __construct(string $pluginCode)
    {
        $this->pluginCode = $pluginCode;
        $this->namespace = 'Plugin\\' . Str::studly($pluginCode);
        $reflection = new \ReflectionClass($this);
        $this->basePath = dirname($reflection->getFileName());
    }

    /**
     * Get plugin code
     */
    public function getPluginCode(): string
    {
        return $this->pluginCode;
    }

    /**
     * Get plugin namespace
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Get plugin base path
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Set configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Get configuration
     */
    public function getConfig(?string $key = null, $default = null): mixed
    {
        $config = $this->config;
        if ($key) {
            $config = $config[$key] ?? $default;
        }
        return $config;
    }

    /**
     * Get view
     */
    protected function view(string $view, array $data = [], array $mergeData = []): \Illuminate\Contracts\View\View
    {
        return view(Str::studly($this->pluginCode) . '::' . $view, $data, $mergeData);
    }

    /**
     * Register action hook listener
     */
    protected function listen(string $hook, callable $callback, int $priority = 20): void
    {
        HookManager::register($hook, $callback, $priority);
    }

    /**
     * Register filter hook
     */
    protected function filter(string $hook, callable $callback, int $priority = 20): void
    {
        HookManager::registerFilter($hook, $callback, $priority);
    }

    /**
     * Remove event listener
     */
    protected function removeListener(string $hook): void
    {
        HookManager::remove($hook);
    }

    /**
     * Register Artisan command
     */
    protected function registerCommand(string $commandClass): void
    {
        if (class_exists($commandClass)) {
            app('Illuminate\Contracts\Console\Kernel')->registerCommand(new $commandClass());
        }
    }

    /**
     * Register plugin commands directory
     */
    public function registerCommands(): void
    {
        $commandsPath = $this->basePath . '/Commands';
        if (File::exists($commandsPath)) {
            $files = File::glob($commandsPath . '/*.php');
            foreach ($files as $file) {
                $className = pathinfo($file, PATHINFO_FILENAME);
                $commandClass = $this->namespace . '\\Commands\\' . $className;
                
                if (class_exists($commandClass)) {
                    $this->registerCommand($commandClass);
                }
            }
        }
    }

    /**
     * Intercept current request and return new response
     *
     * @param Response|string|array $response
     * @return never
     */
    protected function intercept(Response|string|array $response): never
    {
        HookManager::intercept($response);
    }

    /**
     * Called when plugin starts
     */
    public function boot(): void
    {
        // Plugin startup initialization logic
    }

    /**
     * Called when plugin is installed
     */
    public function install(): void
    {
        // Plugin installation initialization logic
    }

    /**
     * Called when plugin is uninstalled
     */
    public function cleanup(): void
    {
        // Plugin cleanup logic
    }

    /**
     * Called when plugin is updated
     */
    public function update(string $oldVersion, string $newVersion): void
    {
        // Plugin update migration logic
    }

    /**
     * Get plugin resource URL
     */
    protected function asset(string $path): string
    {
        return asset('plugins/' . $this->pluginCode . '/' . ltrim($path, '/'));
    }

    /**
     * Get plugin configuration value
     */
    protected function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get plugin database migrations path
     */
    protected function getMigrationsPath(): string
    {
        return $this->basePath . '/database/migrations';
    }

    /**
     * Get plugin views path
     */
    protected function getViewsPath(): string
    {
        return $this->basePath . '/resources/views';
    }

    /**
     * Get plugin assets path
     */
    protected function getAssetsPath(): string
    {
        return $this->basePath . '/resources/assets';
    }

    /**
     * Register plugin scheduled tasks. Plugins can override this method.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    public function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        // Plugin can override this method to register scheduled tasks
    }

    /**
     * Helper method for transferring bytes to GB string
     */
    protected function transferToGBString($bytes): string
    {
        if ($bytes === null || $bytes === 0) {
            return '0';
        }
        return round($bytes / 1073741824, 2);
    }

    /**
     * Success response helper
     */
    protected function success($data = [], string $message = 'Success'): array
    {
        return [
            'data' => $data,
            'message' => $message
        ];
    }

    /**
     * Fail response helper
     */
    protected function fail(array $error): array
    {
        return [
            'error' => $error[0] ?? 500,
            'message' => $error[1] ?? 'Error'
        ];
    }
}
