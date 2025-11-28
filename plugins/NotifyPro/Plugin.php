<?php
/**
 * NotifyPro - Multi-Channel Admin Notifications Plugin for XBoard (EN+ZH)
 *
 * @version 1.6.0
 */

namespace Plugin\NotifyPro;

use App\Models\Order;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Plugin\AbstractPlugin;
use App\Jobs\SendEmailJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ip2Region;

class Plugin extends AbstractPlugin
{
    // ==================== Generic helpers ====================

    protected function formatDate($timestamp): string
    {
        if ($timestamp === null) {
            return date('Y-m-d H:i:s');
        }
        if (is_numeric($timestamp)) {
            return date('Y-m-d H:i:s', (int) $timestamp);
        }
        if ($timestamp instanceof \DateTimeInterface) {
            return $timestamp->format('Y-m-d H:i:s');
        }
        $parsed = strtotime((string) $timestamp);
        if ($parsed !== false) {
            return date('Y-m-d H:i:s', $parsed);
        }
        return date('Y-m-d H:i:s');
    }

    protected function getCurrency(): string
    {
        return $this->getConfig('currency_symbol', '$');
    }

    protected function getLanguageMode(): string
    {
        $mode = strtolower((string) $this->getConfig('language_mode', 'en_zh'));
        if (!in_array($mode, ['en', 'zh', 'en_zh'], true)) {
            $mode = 'en_zh';
        }
        return $mode;
    }

    protected function getRequestInfo(): array
    {
        $userAgent = request()->header('User-Agent', 'Unknown');
        $ip = request()->ip() ?? 'Unknown';

        $browser = 'Unknown';
        $os = 'Unknown';

        if (preg_match('/Chrome\/[\d.]+/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/[\d.]+/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/[\d.]+/i', $userAgent) && !preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge\/[\d.]+/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'IE';
        }

        if (preg_match('/Windows NT/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        }

        return [
            'browser' => $browser,
            'os' => $os,
            'ip' => $ip,
        ];
    }

    // ==================== Boot & hooks ====================

    public function boot(): void
    {
        $this->registerHooks();
        $this->maybeHandleVisitor(); // visitor tracking via InitializePlugins
    }

    protected function registerHooks(): void
    {
        if ($this->getConfig('notify_new_user', true)) {
            $this->listen('user.register.after', [$this, 'handleNewUser']);
        }

        if ($this->getConfig('notify_new_order', true)) {
            $this->listen('order.create.after', [$this, 'handleNewOrder']);
        }

        if ($this->getConfig('notify_order_paid', true)) {
            $this->listen('payment.notify.success', [$this, 'handleOrderPaid']);
            $this->listen('order.open.after', [$this, 'handleOrderPaid']);
        }

        if ($this->getConfig('notify_order_cancelled', true)) {
            $this->listen('order.cancel.after', [$this, 'handleOrderCancelled']);
        }

        if ($this->getConfig('notify_ticket_open', true)) {
            $this->listen('ticket.create.after', [$this, 'handleTicketOpen']);
        }

        if ($this->getConfig('notify_ticket_close', true)) {
            $this->listen('ticket.close.after', [$this, 'handleTicketClose']);
        }
    }

    public function schedule(Schedule $schedule): void
    {
        if ($this->getConfig('notify_node_failure', true)) {
            $interval = (int) $this->getConfig('node_check_interval', '5');
            if ($interval <= 1) {
                $schedule->call([$this, 'checkNodeStatus'])
                    ->everyMinute()
                    ->name('notifypro:check-nodes')
                    ->withoutOverlapping();
            } elseif ($interval === 5) {
                $schedule->call([$this, 'checkNodeStatus'])
                    ->everyFiveMinutes()
                    ->name('notifypro:check-nodes')
                    ->withoutOverlapping();
            } else {
                $schedule->call([$this, 'checkNodeStatus'])
                    ->everyMinutes($interval)
                    ->name('notifypro:check-nodes')
                    ->withoutOverlapping();
            }
        }

        if ($this->getConfig('notify_daily_report', true)) {
            $reportTime = $this->getConfig('daily_report_time', '09:00');
            $schedule->call([$this, 'sendDailyReport'])
                ->dailyAt($reportTime)
                ->name('notifypro:daily-report')
                ->withoutOverlapping();
        }
    }

    // ==================== Visitor tracking ====================

    protected function maybeHandleVisitor(): void
    {
        try {
            if (!$this->getConfig('notify_visitor', false)) {
                return;
            }

            /** @var Request $request */
            $request = request();

            if ($this->shouldSkipVisitorRequest($request)) {
                return;
            }

            $this->handleVisitorRequest($request);
        } catch (\Throwable $e) {
            Log::debug('NotifyPro: maybeHandleVisitor error', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function shouldSkipVisitorRequest(Request $request): bool
    {
        $path = '/' . ltrim($request->path(), '/');

        if (php_sapi_name() === 'cli') {
            return true;
        }

        if (str_starts_with($request->path(), 'api/')) {
            return true;
        }

        if (!$request->isMethod('GET')) {
            return true;
        }

        if (preg_match('#\.(css|js|png|jpg|jpeg|gif|ico|svg|webp|map|woff2?)$#i', $path)) {
            return true;
        }

        // optional: skip admin/backend
        if (str_contains($path, 'admin') || str_contains($path, 'backend')) {
            return true;
        }

        // path whitelist (if set)
        if (!$this->isVisitorPathAllowed($path)) {
            return true;
        }

        $ua = $request->userAgent() ?? '';
        if ($ua === '' || stripos($ua, 'uptimerobot') !== false || stripos($ua, 'health') !== false) {
            return true;
        }

        return false;
    }

    protected function isVisitorPathAllowed(string $path): bool
    {
        $raw = (string) $this->getConfig('notify_visitor_paths', '');
        $raw = trim($raw);
        if ($raw === '') {
            return true;
        }

        $paths = array_filter(array_map('trim', explode(',', $raw)));
        $pathLower = strtolower($path);

        foreach ($paths as $p) {
            $norm = '/' . ltrim($p, '/');
            if (strtolower($norm) === $pathLower) {
                return true;
            }
        }

        return false;
    }

    public function handleVisitorRequest(Request $request): void
    {
        try {
            $minIntervalMinutes = (int) $this->getConfig('notify_visitor_min_interval', 10);
            if ($minIntervalMinutes <= 0) {
                $minIntervalMinutes = 10;
            }

            $ua = $request->userAgent() ?? '';
            $ip = $request->ip() ?? 'Unknown';

            $minUaLen = (int) $this->getConfig('notify_visitor_min_user_agent_length', 20);
            if (mb_strlen($ua) < $minUaLen) {
                return;
            }

            $visitorKeyRaw = $ip . '|' . $ua;
            $visitorKey = 'notifypro:visitor:' . sha1($visitorKeyRaw);

            if (Cache::has($visitorKey)) {
                // still count for stats
                $this->incrementVisitorCounter();
                return;
            }

            Cache::put($visitorKey, true, now()->addMinutes($minIntervalMinutes));
            $this->incrementVisitorCounter();

            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');

            $visitorInfo = $this->buildVisitorInfo($request);

            $title = $this->titleWithLang('👀 New Visitor', '👀 新访客访问');
            $message = $this->buildVisitorMessage($visitorInfo, $siteName, $siteUrl);
            $embedData = $this->buildVisitorEmbed($visitorInfo, $siteName, $siteUrl);

            $this->sendNotifications($title, $message, $embedData, 'visitor');
        } catch (\Throwable $e) {
            Log::warning('NotifyPro: Failed to handle visitor', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function incrementVisitorCounter(): void
    {
        $date = date('Y-m-d');
        $key = "notifypro:visits:{$date}";
        Cache::increment($key);
    }

    protected function getTodayVisitorCount(): int
    {
        $date = date('Y-m-d');
        $key = "notifypro:visits:{$date}";
        return (int) Cache::get($key, 0);
    }

    /**
     * IP -> location (country/city/flag) via ip2region
     */
    protected function lookupIpLocation(string $ip): array
    {
        $country = 'Unknown';
        $region = '';
        $city = '';
        $flag = '';

        try {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $searcher = new Ip2Region();
                $info = $searcher->memorySearch($ip);

                if (!empty($info) && isset($info['region'])) {
                    $parts = explode('|', $info['region']);
                    // 0=country, 2=province, 3=city
                    $country = $parts[0] !== '0' ? $parts[0] : 'Unknown';
                    $region  = $parts[2] !== '0' ? $parts[2] : '';
                    $city    = $parts[3] !== '0' ? $parts[3] : '';
                }

                $flag = $this->countryToFlagEmoji($country);
            }
        } catch (\Throwable $e) {
            Log::debug('NotifyPro: IP location lookup failed', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'country' => $country,
            'region'  => $region,
            'city'    => $city,
            'flag'    => $flag,
        ];
    }

    protected function countryToFlagEmoji(string $country): string
    {
        $country = strtoupper(trim($country));

        $map = [
            'CHINA' => 'CN',
            'PRC' => 'CN',
            'UNITED STATES' => 'US',
            'UNITED STATES OF AMERICA' => 'US',
            'USA' => 'US',
            'HONG KONG' => 'HK',
            'MACAO' => 'MO',
            'TAIWAN' => 'TW',
            'JAPAN' => 'JP',
            'KOREA' => 'KR',
            'SOUTH KOREA' => 'KR',
            'UNITED KINGDOM' => 'GB',
        ];

        $iso = $map[$country] ?? null;

        if (!$iso && preg_match('/^[A-Z]{2}$/', $country)) {
            $iso = $country;
        }

        if (!$iso) {
            return '';
        }

        $first = ord($iso[0]) - 65 + 0x1F1E6;
        $second = ord($iso[1]) - 65 + 0x1F1E6;

        return mb_convert_encoding('&#' . $first . ';', 'UTF-8', 'HTML-ENTITIES')
             . mb_convert_encoding('&#' . $second . ';', 'UTF-8', 'HTML-ENTITIES');
    }

    protected function buildVisitorInfo(Request $request): array
    {
        $ua = $request->userAgent() ?? '';
        $ip = $request->ip() ?? 'Unknown';

        [$browser, $os, $device] = $this->parseVisitorUserAgent($ua);

        $referer = $request->headers->get('referer') ?? 'N/A';
        $fullUrl = $request->fullUrl();
        $path = '/' . ltrim($request->path(), '/');

        $location = [
            'country' => 'Unknown',
            'region'  => '',
            'city'    => '',
            'flag'    => '',
        ];
        if ($this->getConfig('notify_visitor_include_location', true) && filter_var($ip, FILTER_VALIDATE_IP)) {
            $location = $this->lookupIpLocation($ip);
        }

        return [
            'ip'         => $ip,
            'user_agent' => $ua,
            'browser'    => $browser,
            'os'         => $os,
            'device'     => $device,
            'referer'    => $referer,
            'url'        => $fullUrl,
            'path'       => $path,
            'location'   => $location,
        ];
    }

    protected function parseVisitorUserAgent(string $ua): array
    {
        $browser = 'Other';
        if (stripos($ua, 'Chrome/') !== false && stripos($ua, 'Chromium') === false) {
            $browser = 'Chrome';
        } elseif (stripos($ua, 'CriOS/') !== false) {
            $browser = 'Chrome (iOS)';
        } elseif (stripos($ua, 'Firefox/') !== false) {
            $browser = 'Firefox';
        } elseif (stripos($ua, 'Safari/') !== false && stripos($ua, 'Chrome/') === false) {
            $browser = 'Safari';
        } elseif (stripos($ua, 'Edg/') !== false) {
            $browser = 'Edge';
        }

        $os = 'Other';
        $uaLower = strtolower($ua);
        if (str_contains($uaLower, 'windows nt')) {
            $os = 'Windows';
        } elseif (str_contains($uaLower, 'android')) {
            $os = 'Android';
        } elseif (str_contains($uaLower, 'iphone') || str_contains($uaLower, 'ipad')) {
            $os = 'iOS';
        } elseif (str_contains($uaLower, 'mac os x')) {
            $os = 'macOS';
        } elseif (str_contains($uaLower, 'linux')) {
            $os = 'Linux';
        }

        $device = 'Desktop';
        if (str_contains($uaLower, 'mobile') || str_contains($uaLower, 'android') || str_contains($uaLower, 'iphone')) {
            $device = 'Mobile';
        } elseif (str_contains($uaLower, 'ipad') || str_contains($uaLower, 'tablet')) {
            $device = 'Tablet';
        }

        return [$browser, $os, $device];
    }

    protected function buildVisitorMessage(array $v, string $siteName, string $siteUrl): string
    {
        $mode = $this->getLanguageMode();
        $includePath = $this->getConfig('notify_visitor_include_path', true);
        $includeBrowser = $this->getConfig('notify_visitor_include_browser', true);
        $includeReferer = $this->getConfig('notify_visitor_include_referer', true);
        $includeLocation = $this->getConfig('notify_visitor_include_location', true);

        $loc = $v['location'] ?? ['country' => 'Unknown', 'city' => '', 'flag' => ''];
        $locStrEn = 'Unknown';
        $locStrZh = '未知';
        if ($includeLocation && !empty($loc['country']) && $loc['country'] !== 'Unknown') {
            $cityPart = $loc['city'] ? "{$loc['city']}, " : '';
            $locStrEn = "{$cityPart}{$loc['country']} {$loc['flag']}";
            $locStrZh = "{$cityPart}{$loc['country']} {$loc['flag']}";
        }

        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            $lines[] = "🌐 <b>Visitor IP:</b> {$v['ip']}";
            if ($includeLocation) {
                $lines[] = "📍 <b>Location:</b> {$locStrEn}";
            }
            if ($includeBrowser) {
                $lines[] = "🧭 <b>Browser:</b> {$v['browser']} ({$v['os']}, {$v['device']})";
            }
            if ($includePath) {
                $lines[] = "📄 <b>Path:</b> {$v['path']}";
            }
            $lines[] = "🔗 <b>URL:</b> {$v['url']}";
            if ($includeReferer) {
                $lines[] = "↩️ <b>Referer:</b> {$v['referer']}";
            }
            $lines[] = "";
            $lines[] = "🕐 <b>Time:</b> " . date('Y-m-d H:i:s');
            $lines[] = "🏷️ <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            $lines[] = "🌐 <b>访客 IP:</b> {$v['ip']}";
            if ($includeLocation) {
                $lines[] = "📍 <b>地理位置:</b> {$locStrZh}";
            }
            if ($includeBrowser) {
                $lines[] = "🧭 <b>浏览器:</b> {$v['browser']}（{$v['os']}，{$v['device']}）";
            }
            if ($includePath) {
                $lines[] = "📄 <b>访问路径:</b> {$v['path']}";
            }
            $lines[] = "🔗 <b>完整链接:</b> {$v['url']}";
            if ($includeReferer) {
                $lines[] = "↩️ <b>来源 Referer:</b> {$v['referer']}";
            }
            $lines[] = "";
            $lines[] = "🕐 <b>访问时间:</b> " . date('Y-m-d H:i:s');
            $lines[] = "🏷️ <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildVisitorEmbed(array $v, string $siteName, string $siteUrl): array
    {
        $mode = $this->getLanguageMode();
        $includePath = $this->getConfig('notify_visitor_include_path', true);
        $includeBrowser = $this->getConfig('notify_visitor_include_browser', true);
        $includeReferer = $this->getConfig('notify_visitor_include_referer', true);
        $includeLocation = $this->getConfig('notify_visitor_include_location', true);

        $loc = $v['location'] ?? ['country' => 'Unknown', 'city' => '', 'flag' => ''];
        $locStr = 'Unknown';
        if ($includeLocation && !empty($loc['country']) && $loc['country'] !== 'Unknown') {
            $cityPart = $loc['city'] ? "{$loc['city']}, " : '';
            $locStr = "{$cityPart}{$loc['country']} {$loc['flag']}";
        }

        $fields = [
            [
                'name' => $this->fieldName('🌐 IP', '🌐 IP'),
                'value' => $v['ip'],
                'inline' => true
            ],
        ];

        if ($includeLocation) {
            $fields[] = [
                'name' => $this->fieldName('📍 Location', '📍 地理位置'),
                'value' => $locStr,
                'inline' => true
            ];
        }

        if ($includeBrowser) {
            $fields[] = [
                'name' => $this->fieldName('🧭 Browser', '🧭 浏览器'),
                'value' => "{$v['browser']} ({$v['os']}, {$v['device']})",
                'inline' => false
            ];
        }

        if ($includePath) {
            $fields[] = [
                'name' => $this->fieldName('📄 Path', '📄 路径'),
                'value' => $v['path'],
                'inline' => true
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('🔗 URL', '🔗 访问链接'),
            'value' => $v['url'],
            'inline' => false
        ];

        if ($includeReferer) {
            $fields[] = [
                'name' => $this->fieldName('↩️ Referer', '↩️ 来源'),
                'value' => $v['referer'],
                'inline' => false
            ];
        }

        return [
            'title' => $this->titleWithLang('👀 New Visitor', '👀 新访客访问'),
            'description' => $mode === 'zh'
                ? '检测到一位新访客访问站点（按 IP+UA 去重，仅在间隔内首次访问时通知）。'
                : ($mode === 'en'
                    ? 'A new visitor accessed the site (deduped by IP+UA within interval).'
                    : 'A new visitor accessed the site / 检测到一位新访客访问站点'),
            'color' => 0x3498db,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    // ==================== Lang helpers ====================

    protected function titleWithLang(string $en, string $zh): string
    {
        $mode = $this->getLanguageMode();
        return match ($mode) {
            'en'    => $en,
            'zh'    => $zh,
            'en_zh' => "{$en} / {$zh}",
            default => "{$en} / {$zh}",
        };
    }

    protected function fieldName(string $en, string $zh): string
    {
        $mode = $this->getLanguageMode();
        return match ($mode) {
            'en'    => $en,
            'zh'    => $zh,
            'en_zh' => "{$en} / {$zh}",
            default => "{$en} / {$zh}",
        };
    }

    protected function lineLabel(string $en, string $zh): string
    {
        return $this->fieldName($en, $zh);
    }

    // ==================== Event handlers ====================

    public function handleNewUser($user): void
    {
        try {
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');
            $requestInfo = $this->getRequestInfo();

            $title = $this->titleWithLang('🎉 New User Registration', '🎉 新用户注册');
            $message = $this->buildNewUserMessage($user, $siteName, $siteUrl, $requestInfo);
            $embedData = $this->buildNewUserEmbed($user, $siteName, $siteUrl, $requestInfo);

            $this->sendNotifications($title, $message, $embedData, 'new_user');
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to send new user notification', ['error' => $e->getMessage()]);
        }
    }

    public function handleNewOrder($order): void
    {
        try {
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');
            $requestInfo = $this->getRequestInfo();

            $title = $this->titleWithLang('🛒 New Order Created', '🛒 新订单创建');
            $message = $this->buildNewOrderMessage($order, $siteName, $siteUrl, $requestInfo);
            $embedData = $this->buildNewOrderEmbed($order, $siteName, $siteUrl, $requestInfo);

            $this->sendNotifications($title, $message, $embedData, 'new_order');
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to send new order notification', ['error' => $e->getMessage()]);
        }
    }

    public function handleOrderPaid($order): void
    {
        try {
            $cacheKey = "notifypro:order_paid:{$order->id}";
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, true, 600);

            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');

            $title = $this->titleWithLang('💰 Order Paid', '💰 订单已支付');
            $message = $this->buildOrderPaidMessage($order, $siteName, $siteUrl);
            $embedData = $this->buildOrderPaidEmbed($order, $siteName, $siteUrl);

            $this->sendNotifications($title, $message, $embedData, 'order_paid');
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to send order paid notification', ['error' => $e->getMessage()]);
        }
    }

    public function handleOrderCancelled($order): void
    {
        try {
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');

            $title = $this->titleWithLang('❌ Order Cancelled', '❌ 订单已取消');
            $message = $this->buildOrderCancelledMessage($order, $siteName, $siteUrl);
            $embedData = $this->buildOrderCancelledEmbed($order, $siteName, $siteUrl);

            $this->sendNotifications($title, $message, $embedData, 'order_cancelled');
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to send order cancelled notification', ['error' => $e->getMessage()]);
        }
    }

    public function handleTicketOpen($ticket): void
    {
        try {
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');

            $title = $this->titleWithLang('🎫 New Support Ticket', '🎫 新工单创建');
            $message = $this->buildTicketOpenMessage($ticket, $siteName, $siteUrl);
            $embedData = $this->buildTicketOpenEmbed($ticket, $siteName, $siteUrl);

            $this->sendNotifications($title, $message, $embedData, 'ticket_open');
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to send ticket open notification', ['error' => $e->getMessage()]);
        }
    }

    public function handleTicketClose($ticket): void
    {
        try {
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');

            $title = $this->titleWithLang('✅ Ticket Closed', '✅ 工单已关闭');
            $message = $this->buildTicketCloseMessage($ticket, $siteName, $siteUrl);
            $embedData = $this->buildTicketCloseEmbed($ticket, $siteName, $siteUrl);

            $this->sendNotifications($title, $message, $embedData, 'ticket_close');
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to send ticket close notification', ['error' => $e->getMessage()]);
        }
    }

    public function checkNodeStatus(): void
    {
        try {
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');
            $threshold = 300;

            $servers = Server::where('show', 1)->get();

            foreach ($servers as $server) {
                $cacheKey = "SERVER_{$server->type}_LAST_CHECK_AT_{$server->id}";
                $lastCheck = Cache::get($cacheKey);

                if ($lastCheck && (time() - $lastCheck) > $threshold) {
                    $notifiedKey = "notifypro:node_notified:{$server->id}";
                    if (!Cache::has($notifiedKey)) {
                        $title = $this->titleWithLang('⚠️ Node Offline', '⚠️ 节点离线');
                        $message = $this->buildNodeFailureMessage($server, $siteName, $siteUrl);
                        $embedData = $this->buildNodeFailureEmbed($server, $siteName, $siteUrl);

                        $this->sendNotifications($title, $message, $embedData, 'node_failure');
                        Cache::put($notifiedKey, true, 3600);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to check node status', ['error' => $e->getMessage()]);
        }
    }

    public function sendDailyReport(): void
    {
        try {
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');
            $stats = $this->gatherDailyStats();

            $title = $this->titleWithLang('📊 Daily Report', '📊 每日运营报告');
            $message = $this->buildDailyReportMessage($stats, $siteName, $siteUrl);
            $embedData = $this->buildDailyReportEmbed($stats, $siteName, $siteUrl);

            $this->sendNotifications($title, $message, $embedData, 'daily_report');
        } catch (\Exception $e) {
            Log::error('NotifyPro: Failed to send daily report', ['error' => $e->getMessage()]);
        }
    }

    // ==================== Daily stats (with visits_today) ====================

    protected function gatherDailyStats(): array
    {
        $today = strtotime('today');
        $yesterday = strtotime('yesterday');

        return [
            'date' => date('Y-m-d'),
            'new_users_today' => User::where('created_at', '>=', $today)->count(),
            'new_users_yesterday' => User::where('created_at', '>=', $yesterday)
                ->where('created_at', '<', $today)->count(),
            'total_users' => User::count(),
            'active_users' => User::where('expired_at', '>', time())->count(),
            'orders_today' => Order::where('created_at', '>=', $today)->count(),
            'orders_completed_today' => Order::where('created_at', '>=', $today)
                ->where('status', 3)->count(),
            'revenue_today' => Order::where('created_at', '>=', $today)
                ->where('status', 3)->sum('total_amount') / 100,
            'revenue_yesterday' => Order::where('created_at', '>=', $yesterday)
                ->where('created_at', '<', $today)
                ->where('status', 3)->sum('total_amount') / 100,
            'online_nodes' => $this->countOnlineNodes(),
            'offline_nodes' => $this->countOfflineNodes(),
            'total_nodes' => Server::where('show', 1)->count(),
            'visits_today' => $this->getTodayVisitorCount(),
        ];
    }

    protected function countOnlineNodes(): int
    {
        $count = 0;
        $servers = Server::where('show', 1)->get();

        foreach ($servers as $server) {
            $cacheKey = "SERVER_{$server->type}_LAST_CHECK_AT_{$server->id}";
            $lastCheck = Cache::get($cacheKey);
            if ($lastCheck && (time() - $lastCheck) <= 300) {
                $count++;
            }
        }
        return $count;
    }

    protected function countOfflineNodes(): int
    {
        return Server::where('show', 1)->count() - $this->countOnlineNodes();
    }

    // ==================== Channel sending ====================

    protected function sendNotifications(string $title, string $message, array $embedData, string $eventType): void
    {
        if ($this->getConfig('discord_enabled', false)) {
            $this->sendDiscordNotification($title, $message, $embedData);
        }

        if ($this->getConfig('telegram_enabled', false)) {
            $this->sendTelegramNotification($title, $message);
        }

        if ($this->getConfig('email_enabled', false)) {
            $this->sendEmailNotification($title, $message, $eventType);
        }
    }

    protected function sendDiscordNotification(string $title, string $message, array $embedData): void
    {
        $webhookUrl = $this->getConfig('discord_webhook_url', '');
        if (empty($webhookUrl)) {
            return;
        }

        try {
            $embedData['title'] = $title;
            Http::post($webhookUrl, [
                'embeds' => [$embedData],
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyPro: Discord notification failed', ['error' => $e->getMessage()]);
        }
    }

    protected function sendTelegramNotification(string $title, string $message): void
    {
        $botToken = $this->getConfig('telegram_bot_token', '');
        $chatId = $this->getConfig('telegram_chat_id', '');

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        try {
            $fullMessage = "<b>{$title}</b>\n\n{$message}";
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            Http::post($url, [
                'chat_id' => $chatId,
                'text' => $fullMessage,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyPro: Telegram notification failed', ['error' => $e->getMessage()]);
        }
    }

    protected function sendEmailNotification(string $title, string $message, string $eventType): void
    {
        $recipients = $this->getConfig('email_recipients', '');
        if (empty($recipients)) {
            return;
        }

        try {
            $emails = array_map('trim', explode(',', $recipients));
            $siteName = $this->getConfig('site_name', 'XBoard');
            $siteUrl = $this->getConfig('site_url', '');
            $plainTextContent = $this->buildPlainTextContent($title, $message);

            foreach ($emails as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    dispatch(new SendEmailJob([
                        'email' => $email,
                        'subject' => "[{$siteName}] {$title}",
                        'template_name' => 'notify',
                        'template_value' => [
                            'name' => 'Admin',
                            'content' => $plainTextContent,
                            'url' => $siteUrl ?: 'https://example.com',
                        ]
                    ]));
                }
            }
        } catch (\Exception $e) {
            Log::error('NotifyPro: Email notification failed', ['error' => $e->getMessage()]);
        }
    }

    protected function buildPlainTextContent(string $title, string $message): string
    {
        $siteName = $this->getConfig('site_name', 'XBoard');
        $plainMessage = strip_tags(str_replace(['<b>', '</b>', '<strong>', '</strong>'], ['', '', '', ''], $message));

        return "{$title}\n\n" .
               "-------------------------------------------\n\n" .
               "{$plainMessage}\n\n" .
               "-------------------------------------------\n" .
               "Sent by NotifyPro for {$siteName}\n" .
               "Time: " . date('Y-m-d H:i:s');
    }

    // ==================== Message builders for events ====================

    protected function buildNewUserMessage($user, string $siteName, string $siteUrl, array $requestInfo): string
    {
        $email = $user->email ?? 'N/A';
        $userId = $user->id ?? 'N/A';
        $time = $this->formatDate($user->created_at);
        $mode = $this->getLanguageMode();

        $incEmail  = $this->getConfig('alert_include_user_email', true);
        $incUserId = $this->getConfig('alert_include_user_id', true);
        $incIp     = $this->getConfig('alert_include_ip', true);

        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            if ($incEmail) {
                $lines[] = "📧 <b>Email:</b> {$email}";
            }
            if ($incUserId) {
                $lines[] = "🆔 <b>User ID:</b> #{$userId}";
            }
            $lines[] = "🕐 <b>Time:</b> {$time}";
            $lines[] = "🌐 <b>Browser:</b> {$requestInfo['browser']}";
            $lines[] = "💻 <b>OS:</b> {$requestInfo['os']}";
            if ($incIp) {
                $lines[] = "📍 <b>IP:</b> {$requestInfo['ip']}";
            }
            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            if ($incEmail) {
                $lines[] = "📧 <b>邮箱:</b> {$email}";
            }
            if ($incUserId) {
                $lines[] = "🆔 <b>用户ID:</b> #{$userId}";
            }
            $lines[] = "🕐 <b>时间:</b> {$time}";
            $lines[] = "🌐 <b>浏览器:</b> {$requestInfo['browser']}";
            $lines[] = "💻 <b>系统:</b> {$requestInfo['os']}";
            if ($incIp) {
                $lines[] = "📍 <b>IP:</b> {$requestInfo['ip']}";
            }
            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildNewUserEmbed($user, string $siteName, string $siteUrl, array $requestInfo): array
    {
        $mode = $this->getLanguageMode();

        $incEmail  = $this->getConfig('alert_include_user_email', true);
        $incUserId = $this->getConfig('alert_include_user_id', true);
        $incIp     = $this->getConfig('alert_include_ip', true);

        $fields = [];

        if ($incEmail) {
            $fields[] = [
                'name' => $this->fieldName('📧 Email', '📧 邮箱'),
                'value' => $user->email ?? 'N/A',
                'inline' => true
            ];
        }
        if ($incUserId) {
            $fields[] = [
                'name' => $this->fieldName('🆔 User ID', '🆔 用户ID'),
                'value' => '#' . ($user->id ?? 'N/A'),
                'inline' => true
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('🕐 Time', '🕐 时间'),
            'value' => $this->formatDate($user->created_at),
            'inline' => true
        ];
        $fields[] = [
            'name' => $this->fieldName('🌐 Browser', '🌐 浏览器'),
            'value' => $requestInfo['browser'],
            'inline' => true
        ];
        $fields[] = [
            'name' => $this->fieldName('💻 OS', '💻 系统'),
            'value' => $requestInfo['os'],
            'inline' => true
        ];
        if ($incIp) {
            $fields[] = [
                'name' => $this->fieldName('📍 IP', '📍 IP'),
                'value' => $requestInfo['ip'],
                'inline' => true
            ];
        }

        return [
            'title' => $this->titleWithLang('🎉 New User Registration', '🎉 新用户注册'),
            'description' => $mode === 'zh' ? '有新用户注册' : ($mode === 'en' ? 'A new user has registered' : "A new user has registered / 有新用户注册"),
            'color' => 0x00FF00,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    protected function buildNewOrderMessage($order, string $siteName, string $siteUrl, array $requestInfo): string
    {
        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);
        $currency = $this->getCurrency();
        $amount = number_format(($order->total_amount ?? 0) / 100, 2);
        $mode = $this->getLanguageMode();

        $incEmail    = $this->getConfig('alert_include_user_email', true);
        $incUserId   = $this->getConfig('alert_include_user_id', true);
        $incPlan     = $this->getConfig('alert_include_plan_name', true);
        $incIp       = $this->getConfig('alert_include_ip', true);
        $incOrderNo  = $this->getConfig('alert_include_order_no', true);
        $incOrderId  = $this->getConfig('alert_include_order_id', true);

        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            if ($incOrderNo) {
                $lines[] = "📋 <b>Order No:</b> {$order->trade_no}";
            }
            if ($incOrderId) {
                $lines[] = "🆔 <b>Order ID:</b> #{$order->id}";
            }

            if ($incEmail || $incUserId) {
                $userLine = [];
                if ($incEmail) {
                    $userLine[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $userLine[] = "ID: #{$order->user_id}";
                }
                $lines[] = "👤 <b>User:</b> " . implode(' ', $userLine);
            }

            if ($incPlan) {
                $lines[] = "📦 <b>Plan:</b> " . ($plan->name ?? 'N/A');
            }

            $lines[] = "💵 <b>Amount:</b> {$currency}{$amount}";
            $lines[] = "📊 <b>Status:</b> Pending Payment";
            $lines[] = "🕐 <b>Time:</b> " . $this->formatDate($order->created_at);

            if ($incIp) {
                $lines[] = "📍 <b>IP:</b> {$requestInfo['ip']}";
            }

            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            if ($incOrderNo) {
                $lines[] = "📋 <b>订单号:</b> {$order->trade_no}";
            }
            if ($incOrderId) {
                $lines[] = "🆔 <b>订单ID:</b> #{$order->id}";
            }

            if ($incEmail || $incUserId) {
                $userLine = [];
                if ($incEmail) {
                    $userLine[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $userLine[] = "ID: #{$order->user_id}";
                }
                $lines[] = "👤 <b>用户:</b> " . implode(' ', $userLine);
            }

            if ($incPlan) {
                $lines[] = "📦 <b>套餐:</b> " . ($plan->name ?? 'N/A');
            }

            $lines[] = "💵 <b>金额:</b> {$currency}{$amount}";
            $lines[] = "📊 <b>状态:</b> 待支付";
            $lines[] = "🕐 <b>时间:</b> " . $this->formatDate($order->created_at);

            if ($incIp) {
                $lines[] = "📍 <b>IP:</b> {$requestInfo['ip']}";
            }

            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildNewOrderEmbed($order, string $siteName, string $siteUrl, array $requestInfo): array
    {
        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);
        $currency = $this->getCurrency();
        $mode = $this->getLanguageMode();

        $incEmail    = $this->getConfig('alert_include_user_email', true);
        $incUserId   = $this->getConfig('alert_include_user_id', true);
        $incPlan     = $this->getConfig('alert_include_plan_name', true);
        $incIp       = $this->getConfig('alert_include_ip', true);
        $incOrderNo  = $this->getConfig('alert_include_order_no', true);
        $incOrderId  = $this->getConfig('alert_include_order_id', true);

        $fields = [];

        if ($incOrderNo) {
            $fields[] = [
                'name' => $this->fieldName('📋 Order No', '📋 订单号'),
                'value' => $order->trade_no ?? 'N/A',
                'inline' => true
            ];
        }
        if ($incOrderId) {
            $fields[] = [
                'name' => $this->fieldName('🆔 Order ID', '🆔 订单ID'),
                'value' => '#' . ($order->id ?? 'N/A'),
                'inline' => true
            ];
        }

        if ($incEmail || $incUserId) {
            $userLine = [];
            if ($incEmail) {
                $userLine[] = $user->email ?? 'N/A';
            }
            if ($incUserId) {
                $userLine[] = "(#{$order->user_id})";
            }
            $fields[] = [
                'name' => $this->fieldName('👤 User', '👤 用户'),
                'value' => implode(' ', $userLine),
                'inline' => false
            ];
        }

        if ($incPlan) {
            $fields[] = [
                'name' => $this->fieldName('📦 Plan', '📦 套餐'),
                'value' => $plan->name ?? 'N/A',
                'inline' => true
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('💵 Amount', '💵 金额'),
            'value' => $currency . number_format(($order->total_amount ?? 0) / 100, 2),
            'inline' => true
        ];
        $fields[] = [
            'name' => $this->fieldName('🕐 Time', '🕐 时间'),
            'value' => $this->formatDate($order->created_at),
            'inline' => true
        ];

        if ($incIp) {
            $fields[] = [
                'name' => $this->fieldName('📍 IP', '📍 IP'),
                'value' => $requestInfo['ip'],
                'inline' => true
            ];
        }

        return [
            'title' => $this->titleWithLang('🛒 New Order Created', '🛒 新订单创建'),
            'description' => $mode === 'zh'
                ? '有新订单被创建'
                : ($mode === 'en'
                    ? 'A new order has been placed'
                    : 'A new order has been placed / 有新订单被创建'),
            'color' => 0xFFA500,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    protected function buildOrderPaidMessage($order, string $siteName, string $siteUrl): string
    {
        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);
        $currency = $this->getCurrency();
        $amount = number_format(($order->total_amount ?? 0) / 100, 2);
        $mode = $this->getLanguageMode();

        $incEmail    = $this->getConfig('alert_include_user_email', true);
        $incUserId   = $this->getConfig('alert_include_user_id', true);
        $incPlan     = $this->getConfig('alert_include_plan_name', true);
        $incOrderNo  = $this->getConfig('alert_include_order_no', true);
        $incOrderId  = $this->getConfig('alert_include_order_id', true);

        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            if ($incOrderNo) {
                $lines[] = "📋 <b>Order No:</b> {$order->trade_no}";
            }
            if ($incOrderId) {
                $lines[] = "🆔 <b>Order ID:</b> #{$order->id}";
            }
            if ($incEmail || $incUserId) {
                $userLine = [];
                if ($incEmail) {
                    $userLine[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $userLine[] = "ID: #{$order->user_id}";
                }
                $lines[] = "👤 <b>User:</b> " . implode(' ', $userLine);
            }
            if ($incPlan) {
                $lines[] = "📦 <b>Plan:</b> " . ($plan->name ?? 'N/A');
            }
            $lines[] = "💵 <b>Amount:</b> {$currency}{$amount}";
            $lines[] = "✅ <b>Status:</b> Paid";
            $lines[] = "🕐 <b>Time:</b> " . $this->formatDate($order->created_at);
            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            if ($incOrderNo) {
                $lines[] = "📋 <b>订单号:</b> {$order->trade_no}";
            }
            if ($incOrderId) {
                $lines[] = "🆔 <b>订单ID:</b> #{$order->id}";
            }
            if ($incEmail || $incUserId) {
                $userLine = [];
                if ($incEmail) {
                    $userLine[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $userLine[] = "ID: #{$order->user_id}";
                }
                $lines[] = "👤 <b>用户:</b> " . implode(' ', $userLine);
            }
            if ($incPlan) {
                $lines[] = "📦 <b>套餐:</b> " . ($plan->name ?? 'N/A');
            }
            $lines[] = "💵 <b>金额:</b> {$currency}{$amount}";
            $lines[] = "✅ <b>状态:</b> 已支付";
            $lines[] = "🕐 <b>时间:</b> " . $this->formatDate($order->created_at);
            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildOrderPaidEmbed($order, string $siteName, string $siteUrl): array
    {
        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);
        $currency = $this->getCurrency();
        $mode = $this->getLanguageMode();

        $incEmail    = $this->getConfig('alert_include_user_email', true);
        $incUserId   = $this->getConfig('alert_include_user_id', true);
        $incPlan     = $this->getConfig('alert_include_plan_name', true);
        $incOrderNo  = $this->getConfig('alert_include_order_no', true);
        $incOrderId  = $this->getConfig('alert_include_order_id', true);

        $fields = [];

        if ($incOrderNo) {
            $fields[] = [
                'name' => $this->fieldName('📋 Order No', '📋 订单号'),
                'value' => $order->trade_no ?? 'N/A',
                'inline' => true
            ];
        }
        if ($incOrderId) {
            $fields[] = [
                'name' => $this->fieldName('🆔 Order ID', '🆔 订单ID'),
                'value' => '#' . ($order->id ?? 'N/A'),
                'inline' => true
            ];
        }

        if ($incEmail || $incUserId) {
            $userLine = [];
            if ($incEmail) {
                $userLine[] = $user->email ?? 'N/A';
            }
            if ($incUserId) {
                $userLine[] = "(#{$order->user_id})";
            }
            $fields[] = [
                'name' => $this->fieldName('👤 User', '👤 用户'),
                'value' => implode(' ', $userLine),
                'inline' => false
            ];
        }

        if ($incPlan) {
            $fields[] = [
                'name' => $this->fieldName('📦 Plan', '📦 套餐'),
                'value' => $plan->name ?? 'N/A',
                'inline' => true
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('💵 Amount', '💵 金额'),
            'value' => $currency . number_format(($order->total_amount ?? 0) / 100, 2),
            'inline' => true
        ];
        $fields[] = [
            'name' => $this->fieldName('🕐 Time', '🕐 时间'),
            'value' => $this->formatDate($order->created_at),
            'inline' => true
        ];

        return [
            'title' => $this->titleWithLang('💰 Order Paid', '💰 订单已支付'),
            'description' => $mode === 'zh'
                ? '订单已成功支付'
                : ($mode === 'en' ? 'Payment received successfully' : 'Payment received successfully / 订单已成功支付'),
            'color' => 0x00FF00,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    protected function buildOrderCancelledMessage($order, string $siteName, string $siteUrl): string
    {
        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);
        $currency = $this->getCurrency();
        $amount = number_format(($order->total_amount ?? 0) / 100, 2);
        $mode = $this->getLanguageMode();

        $incEmail    = $this->getConfig('alert_include_user_email', true);
        $incUserId   = $this->getConfig('alert_include_user_id', true);
        $incPlan     = $this->getConfig('alert_include_plan_name', true);
        $incOrderNo  = $this->getConfig('alert_include_order_no', true);
        $incOrderId  = $this->getConfig('alert_include_order_id', true);

        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            if ($incOrderNo) {
                $lines[] = "📋 <b>Order No:</b> {$order->trade_no}";
            }
            if ($incOrderId) {
                $lines[] = "🆔 <b>Order ID:</b> #{$order->id}";
            }
            if ($incEmail || $incUserId) {
                $userLine = [];
                if ($incEmail) {
                    $userLine[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $userLine[] = "ID: #{$order->user_id}";
                }
                $lines[] = "👤 <b>User:</b> " . implode(' ', $userLine);
            }
            if ($incPlan) {
                $lines[] = "📦 <b>Plan:</b> " . ($plan->name ?? 'N/A');
            }
            $lines[] = "💵 <b>Amount:</b> {$currency}{$amount}";
            $lines[] = "❌ <b>Status:</b> Cancelled";
            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            if ($incOrderNo) {
                $lines[] = "📋 <b>订单号:</b> {$order->trade_no}";
            }
            if ($incOrderId) {
                $lines[] = "🆔 <b>订单ID:</b> #{$order->id}";
            }
            if ($incEmail || $incUserId) {
                $userLine = [];
                if ($incEmail) {
                    $userLine[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $userLine[] = "ID: #{$order->user_id}";
                }
                $lines[] = "👤 <b>用户:</b> " . implode(' ', $userLine);
            }
            if ($incPlan) {
                $lines[] = "📦 <b>套餐:</b> " . ($plan->name ?? 'N/A');
            }
            $lines[] = "💵 <b>金额:</b> {$currency}{$amount}";
            $lines[] = "❌ <b>状态:</b> 已取消";
            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildOrderCancelledEmbed($order, string $siteName, string $siteUrl): array
    {
        $user = User::find($order->user_id);
        $plan = Plan::find($order->plan_id);
        $currency = $this->getCurrency();
        $mode = $this->getLanguageMode();

        $incEmail    = $this->getConfig('alert_include_user_email', true);
        $incUserId   = $this->getConfig('alert_include_user_id', true);
        $incPlan     = $this->getConfig('alert_include_plan_name', true);
        $incOrderNo  = $this->getConfig('alert_include_order_no', true);
        $incOrderId  = $this->getConfig('alert_include_order_id', true);

        $fields = [];

        if ($incOrderNo) {
            $fields[] = [
                'name' => $this->fieldName('📋 Order No', '📋 订单号'),
                'value' => $order->trade_no ?? 'N/A',
                'inline' => true
            ];
        }
        if ($incOrderId) {
            $fields[] = [
                'name' => $this->fieldName('🆔 Order ID', '🆔 订单ID'),
                'value' => '#' . ($order->id ?? 'N/A'),
                'inline' => true
            ];
        }

        if ($incEmail || $incUserId) {
            $userLine = [];
            if ($incEmail) {
                $userLine[] = $user->email ?? 'N/A';
            }
            if ($incUserId) {
                $userLine[] = "(#{$order->user_id})";
            }
            $fields[] = [
                'name' => $this->fieldName('👤 User', '👤 用户'),
                'value' => implode(' ', $userLine),
                'inline' => false
            ];
        }

        if ($incPlan) {
            $fields[] = [
                'name' => $this->fieldName('📦 Plan', '📦 套餐'),
                'value' => $plan->name ?? 'N/A',
                'inline' => true
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('💵 Amount', '💵 金额'),
            'value' => $currency . number_format(($order->total_amount ?? 0) / 100, 2),
            'inline' => true
        ];

        return [
            'title' => $this->titleWithLang('❌ Order Cancelled', '❌ 订单已取消'),
            'description' => $mode === 'zh'
                ? '订单已被取消'
                : ($mode === 'en' ? 'Order has been cancelled' : 'Order has been cancelled / 订单已被取消'),
            'color' => 0xFF0000,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    // ==================== Ticket message helpers (with full content) ====================

    protected function getTicketContent($ticket): string
    {
        $candidates = [];

        if (!empty($ticket->message)) {
            $candidates[] = $ticket->message;
        }
        if (!empty($ticket->content)) {
            $candidates[] = $ticket->content;
        }
        if (!empty($ticket->last_reply)) {
            $candidates[] = $ticket->last_reply;
        }
        if (!empty($ticket->latest_reply)) {
            $candidates[] = $ticket->latest_reply;
        }

        foreach ($candidates as $raw) {
            if (!is_string($raw)) {
                $raw = json_encode($raw);
            }
            $text = trim($raw);

            if (str_starts_with($text, '[') && str_contains($text, '"message"')) {
                try {
                    $arr = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($arr) && isset($arr[0]['message'])) {
                        $text = (string) $arr[0]['message'];
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if ($text !== '') {
                return mb_strimwidth($text, 0, 500, '...');
            }
        }

        return 'N/A';
    }

    protected function buildTicketOpenMessage($ticket, string $siteName, string $siteUrl): string
    {
        $user = User::find($ticket->user_id);
        $subject = $ticket->subject ?? 'N/A';
        $content = $this->getTicketContent($ticket);
        $mode = $this->getLanguageMode();

        $incEmail  = $this->getConfig('alert_include_user_email', true);
        $incUserId = $this->getConfig('alert_include_user_id', true);

        $contentHtml = nl2br(htmlspecialchars($content));
        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            $lines[] = "🎫 <b>Ticket ID:</b> #{$ticket->id}";
            $lines[] = "📝 <b>Subject:</b> {$subject}";
            if ($incEmail || $incUserId) {
                $u = [];
                if ($incEmail) {
                    $u[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $u[] = "ID: #{$ticket->user_id}";
                }
                $lines[] = "👤 <b>User:</b> " . implode(' ', $u);
            }
            $lines[] = "⚡ <b>Priority:</b> " . $this->getTicketPriority($ticket->level ?? 0);
            $lines[] = "🕐 <b>Time:</b> " . $this->formatDate($ticket->created_at);
            $lines[] = "";
            $lines[] = "💬 <b>Message:</b>";
            $lines[] = $contentHtml;
            $lines[] = "";
            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            $lines[] = "🎫 <b>工单ID:</b> #{$ticket->id}";
            $lines[] = "📝 <b>标题:</b> {$subject}";
            if ($incEmail || $incUserId) {
                $u = [];
                if ($incEmail) {
                    $u[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $u[] = "ID: #{$ticket->user_id}";
                }
                $lines[] = "👤 <b>用户:</b> " . implode(' ', $u);
            }
            $lines[] = "⚡ <b>优先级:</b> " . $this->getTicketPriority($ticket->level ?? 0);
            $lines[] = "🕐 <b>时间:</b> " . $this->formatDate($ticket->created_at);
            $lines[] = "";
            $lines[] = "💬 <b>内容:</b>";
            $lines[] = $contentHtml;
            $lines[] = "";
            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildTicketOpenEmbed($ticket, string $siteName, string $siteUrl): array
    {
        $user = User::find($ticket->user_id);
        $subject = $ticket->subject ?? 'N/A';
        $content = $this->getTicketContent($ticket);
        $mode = $this->getLanguageMode();

        $incEmail  = $this->getConfig('alert_include_user_email', true);
        $incUserId = $this->getConfig('alert_include_user_id', true);

        $fields = [
            [
                'name' => $this->fieldName('🎫 Ticket ID', '🎫 工单ID'),
                'value' => '#' . ($ticket->id ?? 'N/A'),
                'inline' => true
            ],
            [
                'name' => $this->fieldName('⚡ Priority', '⚡ 优先级'),
                'value' => $this->getTicketPriority($ticket->level ?? 0),
                'inline' => true
            ],
            [
                'name' => $this->fieldName('📝 Subject', '📝 标题'),
                'value' => $subject,
                'inline' => false
            ],
        ];

        if ($incEmail || $incUserId) {
            $u = [];
            if ($incEmail) {
                $u[] = $user->email ?? 'N/A';
            }
            if ($incUserId) {
                $u[] = "(#{$ticket->user_id})";
            }
            $fields[] = [
                'name' => $this->fieldName('👤 User', '👤 用户'),
                'value' => implode(' ', $u),
                'inline' => false
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('💬 Message', '💬 内容'),
            'value' => mb_strimwidth($content, 0, 200, '...'),
            'inline' => false
        ];
        $fields[] = [
            'name' => $this->fieldName('🕐 Time', '🕐 时间'),
            'value' => $this->formatDate($ticket->created_at),
            'inline' => true
        ];

        return [
            'title' => $this->titleWithLang('🎫 New Support Ticket', '🎫 新工单创建'),
            'description' => $mode === 'zh'
                ? '有新的工单被创建'
                : ($mode === 'en' ? 'A new ticket has been opened' : 'A new ticket has been opened / 有新的工单被创建'),
            'color' => 0x0099FF,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    protected function buildTicketCloseMessage($ticket, string $siteName, string $siteUrl): string
    {
        $user = User::find($ticket->user_id);
        $subject = $ticket->subject ?? 'N/A';
        $content = $this->getTicketContent($ticket);
        $mode = $this->getLanguageMode();

        $incEmail  = $this->getConfig('alert_include_user_email', true);
        $incUserId = $this->getConfig('alert_include_user_id', true);

        $contentHtml = nl2br(htmlspecialchars($content));
        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            $lines[] = "🎫 <b>Ticket ID:</b> #{$ticket->id}";
            $lines[] = "📝 <b>Subject:</b> {$subject}";
            if ($incEmail || $incUserId) {
                $u = [];
                if ($incEmail) {
                    $u[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $u[] = "ID: #{$ticket->user_id}";
                }
                $lines[] = "👤 <b>User:</b> " . implode(' ', $u);
            }
            $lines[] = "✅ <b>Status:</b> Closed";
            $lines[] = "";
            $lines[] = "💬 <b>Message:</b>";
            $lines[] = $contentHtml;
            $lines[] = "";
            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            $lines[] = "🎫 <b>工单ID:</b> #{$ticket->id}";
            $lines[] = "📝 <b>标题:</b> {$subject}";
            if ($incEmail || $incUserId) {
                $u = [];
                if ($incEmail) {
                    $u[] = $user->email ?? 'N/A';
                }
                if ($incUserId) {
                    $u[] = "ID: #{$ticket->user_id}";
                }
                $lines[] = "👤 <b>用户:</b> " . implode(' ', $u);
            }
            $lines[] = "✅ <b>状态:</b> 已关闭";
            $lines[] = "";
            $lines[] = "💬 <b>内容:</b>";
            $lines[] = $contentHtml;
            $lines[] = "";
            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildTicketCloseEmbed($ticket, string $siteName, string $siteUrl): array
    {
        $user = User::find($ticket->user_id);
        $subject = $ticket->subject ?? 'N/A';
        $content = $this->getTicketContent($ticket);
        $mode = $this->getLanguageMode();

        $incEmail  = $this->getConfig('alert_include_user_email', true);
        $incUserId = $this->getConfig('alert_include_user_id', true);

        $fields = [
            [
                'name' => $this->fieldName('🎫 Ticket ID', '🎫 工单ID'),
                'value' => '#' . ($ticket->id ?? 'N/A'),
                'inline' => true
            ],
            [
                'name' => $this->fieldName('📝 Subject', '📝 标题'),
                'value' => $subject,
                'inline' => false
            ],
        ];

        if ($incEmail || $incUserId) {
            $u = [];
            if ($incEmail) {
                $u[] = $user->email ?? 'N/A';
            }
            if ($incUserId) {
                $u[] = "(#{$ticket->user_id})";
            }
            $fields[] = [
                'name' => $this->fieldName('👤 User', '👤 用户'),
                'value' => implode(' ', $u),
                'inline' => false
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('💬 Message', '💬 内容'),
            'value' => mb_strimwidth($content, 0, 200, '...'),
            'inline' => false
        ];

        return [
            'title' => $this->titleWithLang('✅ Ticket Closed', '✅ 工单已关闭'),
            'description' => $mode === 'zh'
                ? '工单已解决并关闭'
                : ($mode === 'en' ? 'A ticket has been resolved' : 'A ticket has been resolved / 工单已解决并关闭'),
            'color' => 0x00FF00,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    // ==================== Node & daily report message builders ====================

    protected function buildNodeFailureMessage($node, string $siteName, string $siteUrl): string
    {
        $mode = $this->getLanguageMode();
        $incNodeId   = $this->getConfig('alert_include_node_id', true);
        $incNodeHost = $this->getConfig('alert_include_node_host', true);

        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            $lines[] = "🖥️ <b>Node:</b> " . ($node->name ?? 'Unknown');
            if ($incNodeId) {
                $lines[] = "🆔 <b>Node ID:</b> #{$node->id}";
            }
            if ($incNodeHost) {
                $lines[] = "🌐 <b>Host:</b> " . ($node->host ?? 'N/A');
            }
            $lines[] = "📡 <b>Type:</b> " . strtoupper($node->type ?? 'Unknown');
            $lines[] = "⚠️ <b>Status:</b> OFFLINE";
            $lines[] = "🕐 <b>Detected:</b> " . date('Y-m-d H:i:s');
            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            $lines[] = "🖥️ <b>节点:</b> " . ($node->name ?? 'Unknown');
            if ($incNodeId) {
                $lines[] = "🆔 <b>节点ID:</b> #{$node->id}";
            }
            if ($incNodeHost) {
                $lines[] = "🌐 <b>主机:</b> " . ($node->host ?? 'N/A');
            }
            $lines[] = "📡 <b>类型:</b> " . strtoupper($node->type ?? 'Unknown');
            $lines[] = "⚠️ <b>状态:</b> 离线";
            $lines[] = "🕐 <b>检测时间:</b> " . date('Y-m-d H:i:s');
            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildNodeFailureEmbed($node, string $siteName, string $siteUrl): array
    {
        $mode = $this->getLanguageMode();
        $incNodeId   = $this->getConfig('alert_include_node_id', true);
        $incNodeHost = $this->getConfig('alert_include_node_host', true);

        $fields = [
            [
                'name' => $this->fieldName('🖥️ Node', '🖥️ 节点'),
                'value' => $node->name ?? 'Unknown',
                'inline' => true
            ],
        ];

        if ($incNodeId) {
            $fields[] = [
                'name' => $this->fieldName('🆔 Node ID', '🆔 节点ID'),
                'value' => '#' . ($node->id ?? 'N/A'),
                'inline' => true
            ];
        }
        if ($incNodeHost) {
            $fields[] = [
                'name' => $this->fieldName('🌐 Host', '🌐 主机'),
                'value' => $node->host ?? 'N/A',
                'inline' => true
            ];
        }

        $fields[] = [
            'name' => $this->fieldName('📡 Type', '📡 类型'),
            'value' => strtoupper($node->type ?? 'Unknown'),
            'inline' => true
        ];
        $fields[] = [
            'name' => $this->fieldName('🕐 Detected', '🕐 检测时间'),
            'value' => date('Y-m-d H:i:s'),
            'inline' => true
        ];

        return [
            'title' => $this->titleWithLang('⚠️ Node Offline', '⚠️ 节点离线'),
            'description' => $mode === 'zh'
                ? '有服务器节点掉线'
                : ($mode === 'en'
                    ? 'A server node has gone offline'
                    : 'A server node has gone offline / 有服务器节点掉线'),
            'color' => 0xFF0000,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    protected function buildDailyReportMessage(array $stats, string $siteName, string $siteUrl): string
    {
        $currency = $this->getCurrency();
        $growth = $stats['new_users_today'] - $stats['new_users_yesterday'];
        $growthIcon = $growth >= 0 ? '📈' : '📉';
        $mode = $this->getLanguageMode();

        $incUsers   = $this->getConfig('daily_report_include_users', true);
        $incOrders  = $this->getConfig('daily_report_include_orders', true);
        $incRev     = $this->getConfig('daily_report_include_revenue', true);
        $incNodes   = $this->getConfig('daily_report_include_nodes', true);
        $incVisits  = $this->getConfig('daily_report_include_visits', true);

        $lines = [];

        if ($mode === 'en' || $mode === 'en_zh') {
            $lines[] = "📅 <b>Date:</b> {$stats['date']}";
            $lines[] = "";

            if ($incUsers) {
                $lines[] = "<b>👥 USERS</b>";
                $lines[] = "• New Today: {$stats['new_users_today']} {$growthIcon}";
                $lines[] = "• Active: {$stats['active_users']}";
                $lines[] = "• Total: {$stats['total_users']}";
                $lines[] = "";
            }

            if ($incOrders) {
                $lines[] = "<b>📦 ORDERS</b>";
                $lines[] = "• Created: {$stats['orders_today']}";
                $lines[] = "• Completed: {$stats['orders_completed_today']}";
                $lines[] = "";
            }

            if ($incRev) {
                $lines[] = "<b>💰 REVENUE</b>";
                $lines[] = "• Today: {$currency}" . number_format($stats['revenue_today'], 2);
                $lines[] = "• Yesterday: {$currency}" . number_format($stats['revenue_yesterday'], 2);
                $lines[] = "";
            }

            if ($incNodes) {
                $lines[] = "<b>🖥️ NODES</b>";
                $lines[] = "• Online: {$stats['online_nodes']}/{$stats['total_nodes']}";
                $lines[] = "• Offline: {$stats['offline_nodes']}";
                $lines[] = "";
            }

            if ($incVisits) {
                $lines[] = "<b>📈 VISITS</b>";
                $lines[] = "• Visits Today: {$stats['visits_today']}";
                $lines[] = "";
            }

            $lines[] = "🔗 <b>Site:</b> {$siteName}";
        }

        if ($mode === 'en_zh') {
            $lines[] = "";
        }

        if ($mode === 'zh' || $mode === 'en_zh') {
            $lines[] = "📅 <b>日期:</b> {$stats['date']}";
            $lines[] = "";

            if ($incUsers) {
                $lines[] = "<b>👥 用户</b>";
                $lines[] = "• 今日新增: {$stats['new_users_today']} {$growthIcon}";
                $lines[] = "• 活跃用户: {$stats['active_users']}";
                $lines[] = "• 总用户数: {$stats['total_users']}";
                $lines[] = "";
            }

            if ($incOrders) {
                $lines[] = "<b>📦 订单</b>";
                $lines[] = "• 今日创建: {$stats['orders_today']}";
                $lines[] = "• 今日完成: {$stats['orders_completed_today']}";
                $lines[] = "";
            }

            if ($incRev) {
                $lines[] = "<b>💰 收入</b>";
                $lines[] = "• 今日: {$currency}" . number_format($stats['revenue_today'], 2);
                $lines[] = "• 昨日: {$currency}" . number_format($stats['revenue_yesterday'], 2);
                $lines[] = "";
            }

            if ($incNodes) {
                $lines[] = "<b>🖥️ 节点</b>";
                $lines[] = "• 在线: {$stats['online_nodes']}/{$stats['total_nodes']}";
                $lines[] = "• 离线: {$stats['offline_nodes']}";
                $lines[] = "";
            }

            if ($incVisits) {
                $lines[] = "<b>📈 访问</b>";
                $lines[] = "• 今日访问量: {$stats['visits_today']}";
                $lines[] = "";
            }

            $lines[] = "🔗 <b>站点:</b> {$siteName}";
        }

        return implode("\n", $lines);
    }

    protected function buildDailyReportEmbed(array $stats, string $siteName, string $siteUrl): array
    {
        $currency = $this->getCurrency();
        $growth = $stats['new_users_today'] - $stats['new_users_yesterday'];
        $growthIcon = $growth >= 0 ? '📈' : '📉';
        $mode = $this->getLanguageMode();

        $incUsers   = $this->getConfig('daily_report_include_users', true);
        $incOrders  = $this->getConfig('daily_report_include_orders', true);
        $incRev     = $this->getConfig('daily_report_include_revenue', true);
        $incNodes   = $this->getConfig('daily_report_include_nodes', true);
        $incVisits  = $this->getConfig('daily_report_include_visits', true);

        $fields = [];

        if ($incUsers) {
            $fields[] = [
                'name' => $this->fieldName('👥 New Users', '👥 新增用户'),
                'value' => "{$stats['new_users_today']} {$growthIcon}",
                'inline' => true
            ];
            $fields[] = [
                'name' => $this->fieldName('👥 Active', '👥 活跃用户'),
                'value' => (string) $stats['active_users'],
                'inline' => true
            ];
            $fields[] = [
                'name' => $this->fieldName('👥 Total', '👥 总用户'),
                'value' => (string) $stats['total_users'],
                'inline' => true
            ];
        }

        if ($incOrders) {
            $fields[] = [
                'name' => $this->fieldName('📦 Orders', '📦 订单数'),
                'value' => (string) $stats['orders_today'],
                'inline' => true
            ];
            $fields[] = [
                'name' => $this->fieldName('✅ Completed', '✅ 完成订单'),
                'value' => (string) $stats['orders_completed_today'],
                'inline' => true
            ];
        }

        if ($incRev) {
            $fields[] = [
                'name' => $this->fieldName('💰 Revenue Today', '💰 今日收入'),
                'value' => $currency . number_format($stats['revenue_today'], 2),
                'inline' => true
            ];
            $fields[] = [
                'name' => $this->fieldName('💰 Revenue Yesterday', '💰 昨日收入'),
                'value' => $currency . number_format($stats['revenue_yesterday'], 2),
                'inline' => true
            ];
        }

        if ($incNodes) {
            $fields[] = [
                'name' => $this->fieldName('🖥️ Nodes Online', '🖥️ 在线节点'),
                'value' => "{$stats['online_nodes']}/{$stats['total_nodes']}",
                'inline' => true
            ];
            $fields[] = [
                'name' => $this->fieldName('🖥️ Nodes Offline', '🖥️ 离线节点'),
                'value' => (string) $stats['offline_nodes'],
                'inline' => true
            ];
        }

        if ($incVisits) {
            $fields[] = [
                'name' => $this->fieldName('📈 Visits Today', '📈 今日访问'),
                'value' => (string) $stats['visits_today'],
                'inline' => true
            ];
        }

        return [
            'title' => $this->titleWithLang("📊 Daily Report - {$stats['date']}", "📊 每日报告 - {$stats['date']}"),
            'description' => $mode === 'zh'
                ? '每日数据汇总'
                : ($mode === 'en' ? 'Daily statistics summary' : 'Daily statistics summary / 每日数据汇总'),
            'color' => 0x667eea,
            'fields' => $fields,
            'footer' => ['text' => "{$siteName} • NotifyPro"],
            'timestamp' => date('c'),
        ];
    }

    protected function getTicketPriority(int $level): string
    {
        return match ($level) {
            0 => '🟢 Low / 低',
            1 => '🟡 Medium / 中',
            2 => '🔴 High / 高',
            default => '⚪ Unknown / 未知',
        };
    }
}