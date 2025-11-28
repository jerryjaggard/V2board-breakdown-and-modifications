<?php

use App\Services\ThemeService;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function (Request $request) {
    if (config('v2board.app_url') && config('v2board.safe_mode_enable', 0)) {
        if ($request->server('HTTP_HOST') !== parse_url(config('v2board.app_url'))['host']) {
            abort(403);
        }
    }
    $renderParams = [
        'title' => config('v2board.app_name', 'V2Board'),
        'theme' => config('v2board.frontend_theme', 'v2board'),
        'version' => config('app.version'),
        'description' => config('v2board.app_description', 'V2Board is best'),
        'logo' => config('v2board.logo')
    ];

    if (!config("theme.{$renderParams['theme']}")) {
        $themeService = new ThemeService($renderParams['theme']);
        $themeService->init();
    }

    $renderParams['theme_config'] = config('theme.' . config('v2board.frontend_theme', 'v2board'));
    return view('theme::' . config('v2board.frontend_theme', 'v2board') . '.dashboard', $renderParams);
});

//TODO:: 兼容
Route::get('/' . config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key')))), function () {
    return view('admin', [
        'title' => config('v2board.app_name', 'V2Board'),
        'theme_sidebar' => config('v2board.frontend_theme_sidebar', 'light'),
        'theme_header' => config('v2board.frontend_theme_header', 'dark'),
        'theme_color' => config('v2board.frontend_theme_color', 'default'),
        'background_url' => config('v2board.frontend_background_url'),
        'version' => config('app.version'),
        'logo' => config('v2board.logo'),
        'secure_path' => config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key'))))
    ]);
});

// Plugin Management Dashboard (Legacy)
Route::get('/' . config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key')))) . '/plugins', function () {
    return view('plugins', [
        'title' => config('v2board.app_name', 'V2Board'),
        'secure_path' => config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key'))))
    ]);
});

// ============================================================================
// NEW ADMIN PANEL (admin2) - Modern Blade-based admin with native plugin support
// ============================================================================
$securePath = config('v2board.secure_path', config('v2board.frontend_admin_path', hash('crc32b', config('app.key'))));
$admin2Params = [
    'secure_path' => $securePath
];

// Admin2 Login
Route::get('/' . $securePath . '/admin2/login', function () use ($admin2Params) {
    return view('admin2.login', $admin2Params);
});

// Admin2 Dashboard and Pages
Route::get('/' . $securePath . '/admin2', function () use ($admin2Params) {
    return view('admin2.dashboard', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/users', function () use ($admin2Params) {
    return view('admin2.users', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/orders', function () use ($admin2Params) {
    return view('admin2.orders', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/plans', function () use ($admin2Params) {
    return view('admin2.plans', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/servers', function () use ($admin2Params) {
    return view('admin2.servers', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/tickets', function () use ($admin2Params) {
    return view('admin2.tickets', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/payments', function () use ($admin2Params) {
    return view('admin2.payments', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/coupons', function () use ($admin2Params) {
    return view('admin2.coupons', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/knowledge', function () use ($admin2Params) {
    return view('admin2.knowledge', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/notices', function () use ($admin2Params) {
    return view('admin2.notices', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/plugins', function () use ($admin2Params) {
    return view('admin2.plugins', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/settings', function () use ($admin2Params) {
    return view('admin2.settings', $admin2Params);
});

Route::get('/' . $securePath . '/admin2/system', function () use ($admin2Params) {
    return view('admin2.system', $admin2Params);
});
