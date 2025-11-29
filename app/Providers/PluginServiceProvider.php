<?php

namespace App\Providers;

use App\Services\Plugin\PluginManager;
use Illuminate\Support\ServiceProvider;

class PluginServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(PluginManager::class, function ($app) {
            return new PluginManager();
        });
    }

    public function boot(): void
    {
        // Create plugins directory if it doesn't exist
        if (!file_exists(base_path('plugins'))) {
            mkdir(base_path('plugins'), 0755, true);
        }
    }
}
