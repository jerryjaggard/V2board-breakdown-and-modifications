<?php

namespace App\Http\Controllers;

use App\Traits\HasPluginConfig;

/**
 * Plugin Controller Base Class
 * 
 * Provides common functionality for all plugin controllers
 */
abstract class PluginController extends Controller
{
    use HasPluginConfig;

    /**
     * Check before plugin action
     */
    protected function beforePluginAction(): ?array
    {
        if (!$this->isPluginEnabled()) {
            return [400, 'Plugin is not enabled'];
        }
        return null;
    }

    /**
     * Success response
     */
    protected function success($data = [], string $message = 'Success')
    {
        return response()->json([
            'data' => $data,
            'message' => $message
        ]);
    }

    /**
     * Fail response
     */
    protected function fail(array $error)
    {
        return response()->json([
            'message' => $error[1] ?? 'Error'
        ], $error[0] ?? 500);
    }
}
