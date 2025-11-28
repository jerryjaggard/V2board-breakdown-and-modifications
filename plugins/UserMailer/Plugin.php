<?php
/**
 * UserMailer - Customer Email Notifications Plugin for XBoard
 *
 * v1.7.0
 * - Multilingual (EN / ZH / EN+ZH / AUTO) content
 * - AUTO: 中国常见邮箱域（qq/163/126/139/aliyun/sina/yeah.net/sohu 等）发中文，其它发英文
 * - 品牌与订单信息只在 Footer 展示，避免标题“变量堆砌”
 * - 强营销文案：欢迎、订单折扣提醒、付款确认升级优惠、召回优惠
 * - 全局联系方式 Footer（含备用站点 + 支付问题引导）
 */

namespace Plugin\UserMailer;

use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Services\Plugin\AbstractPlugin;
use App\Jobs\SendEmailJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Plugin extends AbstractPlugin
{
    public function boot(): void
    {
        if (!$this->getConfig('enabled', true)) {
            return;
        }

        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        if ($this->getConfig('send_welcome_email', true)) {
            $this->listen('user.register.after', [$this, 'handleNewUser']);
        }

        if ($this->getConfig('send_order_created', true)) {
            $this->listen('order.create.after', [$this, 'handleOrderCreated']);
        }

        if ($this->getConfig('send_order_paid', true)) {
            $this->listen('payment.notify.success', [$this, 'handleOrderPaid']);
            $this->listen('order.open.after', [$this, 'handleOrderPaid']);
        }

        if ($this->getConfig('send_reactivation_email', true)) {
            $this->listen('order.cancel.after', [$this, 'handleOrderCancelled']);
            // 如果有订阅到期事件，也可以挂这里：
            // $this->listen('order.expire.after', [$this, 'handleOrderCancelled']);
        }
    }

    public function schedule(Schedule $schedule): void
    {
        if ($this->getConfig('send_unpaid_reminder', true)) {
            $schedule->call([$this, 'sendUnpaidReminders'])
                ->everyThirtyMinutes()
                ->name('usermailer:unpaid-reminders')
                ->withoutOverlapping();
        }
    }

    /* ========== LANGUAGE HELPERS ========== */

    protected function getGlobalEmailLanguageMode(): string
    {
        $mode = strtolower((string) $this->getConfig('email_language_mode', 'auto'));
        if (!in_array($mode, ['en', 'zh', 'en_zh', 'auto'], true)) {
            $mode = 'auto';
        }
        return $mode;
    }

    protected function getEmailLanguageModeForUser(?User $user): string
    {
        $global = $this->getGlobalEmailLanguageMode();

        if ($global !== 'auto') {
            return $global;
        }

        if (!$user || !$user->email) {
            return 'en';
        }

        $email = strtolower($user->email);
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return 'en';
        }

        $cnDomains = [
            'qq.com',
            '163.com',
            '126.com',
            '139.com',
            '189.cn',
            'sina.com',
            'sina.cn',
            'aliyun.com',
            'aliyun.cn',
            'yeah.net',
            'sohu.com',
            'vip.qq.com',
            'vip.163.com',
        ];

        if (in_array($domain, $cnDomains, true)) {
            return 'zh';
        }

        return 'en';
    }

    protected function buildBilingualForUser(
        ?User $user,
        string $baseKey,
        string $defaultEn,
        string $defaultZh,
        array $vars = []
    ): string {
        $mode = $this->getEmailLanguageModeForUser($user);

        $enCfgKey = $baseKey;
        $zhCfgKey = $baseKey . '_zh';

        $en = $this->getConfig($enCfgKey, $defaultEn);
        $zh = $this->getConfig($zhCfgKey, $defaultZh);

        foreach ($vars as $k => $v) {
            $en = str_replace($k, $v, $en);
            $zh = str_replace($k, $v, $zh);
        }

        return match ($mode) {
            'en'    => $en,
            'zh'    => $zh,
            'en_zh' => trim($en) . "\n\n" . trim($zh),
            default => trim($en) . "\n\n" . trim($zh),
        };
    }

    protected function buildContent(string $configKey, string $default, array $vars = []): string
    {
        $content = $this->getConfig($configKey, $default);
        foreach ($vars as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        return $content;
    }

    protected function buildBilingualContentForUser(
        ?User $user,
        string $baseKey,
        string $defaultEn,
        string $defaultZh,
        array $vars = []
    ): string {
        $mode = $this->getEmailLanguageModeForUser($user);

        $enKey = $baseKey;
        $zhKey = $baseKey . '_zh';

        $en = $this->buildContent($enKey, $defaultEn, $vars);
        $zh = $this->buildContent($zhKey, $defaultZh, $vars);

        return match ($mode) {
            'en'    => $en,
            'zh'    => $zh,
            'en_zh' => trim($en) . "\n\n" . trim($zh),
            default => trim($en) . "\n\n" . trim($zh),
        };
    }

    /* ========== BRANDING & FOOTER ========== */

    protected function buildOrderSummaryLine(?Order $order, ?Plan $plan = null, ?User $user = null): string
    {
        if (!$order || !$this->getConfig('branding_include_order_summary', true)) {
            return '';
        }

        $currency = $this->getConfig('currency_symbol', '$');
        $amount = number_format($order->total_amount / 100, 2);
        $parts = [];

        $parts[] = "Order: " . ($order->trade_no ?: $order->id);
        if ($plan) {
            $parts[] = "Plan: " . $plan->name;
        }
        $parts[] = "Amount: {$currency}{$amount}";

        $enLine = 'Summary • ' . implode(' | ', $parts);
        $zhLine = '订单摘要 • ' . implode(' | ', $parts);

        $mode = $this->getEmailLanguageModeForUser($user);

        return match ($mode) {
            'en'    => $enLine,
            'zh'    => $zhLine,
            'en_zh' => $enLine . "\n" . $zhLine,
            default => $enLine . "\n" . $zhLine,
        };
    }

    protected function buildContactFooter(?Order $order = null, ?Plan $plan = null, ?User $user = null): string
    {
        if (!$this->getConfig('contact_footer_enabled', true)) {
            return '';
        }

        $mode = $this->getEmailLanguageModeForUser($user);

        $supportEmail  = trim((string) $this->getConfig('contact_support_email', ''));
        $website       = trim((string) $this->getConfig('contact_website_url', ''));
        $backupWebsite = trim((string) $this->getConfig('contact_backup_website_url', ''));
        $tgGroup       = trim((string) $this->getConfig('contact_telegram_group', ''));
        $tgId          = trim((string) $this->getConfig('contact_telegram_id', ''));
        $wechat        = trim((string) $this->getConfig('contact_wechat_id', ''));
        $instagram     = trim((string) $this->getConfig('contact_instagram', ''));
        $linkedin      = trim((string) $this->getConfig('contact_linkedin', ''));
        $facebook      = trim((string) $this->getConfig('contact_facebook', ''));
        $logoUrl       = trim((string) $this->getConfig('branding_logo_url', ''));
        $showBranding  = $this->getConfig('email_branding_enabled', true);
        $showSiteName  = $this->getConfig('branding_include_site_name', true);
        $siteName      = $this->getConfig('site_name', 'Netflare');

        $summaryLine = $showBranding ? $this->buildOrderSummaryLine($order, $plan, $user) : '';

        $linesEn = [];
        $linesZh = [];

        $linesEn[] = "------------------------------";
        if ($showBranding) {
            if ($logoUrl !== '') {
                $linesEn[] = "[LOGO] {$logoUrl}";
            }
            if ($showSiteName) {
                $linesEn[] = "Service: {$siteName}";
            }
            if ($summaryLine !== '') {
                $linesEn[] = $summaryLine;
            }
        }
        $linesEn[] = "";
        $linesEn[] = "If you ran into any problem (especially with payment or setup), or if you have questions, please reach out:";
        if ($supportEmail !== '') {
            $linesEn[] = "• Support Email: {$supportEmail}";
        }
        if ($website !== '') {
            $linesEn[] = "• Website: {$website}";
        }
        if ($backupWebsite !== '') {
            $linesEn[] = "• Backup site (if main site is blocked): {$backupWebsite}";
        }
        if ($tgGroup !== '') {
            $linesEn[] = "• Telegram Group: {$tgGroup}";
        }
        if ($tgId !== '') {
            $linesEn[] = "• Telegram: {$tgId}";
        }
        if ($wechat !== '') {
            $linesEn[] = "• WeChat: {$wechat}";
        }
        if ($instagram !== '') {
            $linesEn[] = "• Instagram: {$instagram}";
        }
        if ($linkedin !== '') {
            $linesEn[] = "• LinkedIn: {$linkedin}";
        }
        if ($facebook !== '') {
            $linesEn[] = "• Facebook: {$facebook}";
        }

        $linesZh[] = "------------------------------";
        if ($showBranding) {
            if ($logoUrl !== '') {
                $linesZh[] = "[LOGO] {$logoUrl}";
            }
            if ($showSiteName) {
                $linesZh[] = "服务名称：{$siteName}";
            }
            if ($summaryLine !== '') {
                $linesZh[] = $summaryLine;
            }
        }
        $linesZh[] = "";
        $linesZh[] = "如果你在使用或支付过程中遇到任何问题（例如支付失败、页面报错、不会配置），或者对套餐有任何疑问，都可以通过以下方式联系我们：";
        if ($supportEmail !== '') {
            $linesZh[] = "• 客服邮箱：{$supportEmail}";
        }
        if ($website !== '') {
            $linesZh[] = "• 官网地址：{$website}";
        }
        if ($backupWebsite !== '') {
            $linesZh[] = "• 备用站点：{$backupWebsite}";
        }
        if ($tgGroup !== '') {
            $linesZh[] = "• Telegram 群组：{$tgGroup}";
        }
        if ($tgId !== '') {
            $linesZh[] = "• Telegram 联系：{$tgId}";
        }
        if ($wechat !== '') {
            $linesZh[] = "• 微信客服：{$wechat}";
        }
        if ($instagram !== '') {
            $linesZh[] = "• Instagram：{$instagram}";
        }
        if ($linkedin !== '') {
            $linesZh[] = "• LinkedIn：{$linkedin}";
        }
        if ($facebook !== '') {
            $linesZh[] = "• Facebook：{$facebook}";
        }

        $footerEn = implode("\n", $linesEn);
        $footerZh = implode("\n", $linesZh);

        return match ($mode) {
            'en'    => "\n\n" . $footerEn,
            'zh'    => "\n\n" . $footerZh,
            'en_zh' => "\n\n" . $footerEn . "\n\n" . $footerZh,
            default => "\n\n" . $footerEn . "\n\n" . $footerZh,
        };
    }

    /* ========== EVENT HANDLERS ========== */

    public function handleNewUser($user): void
    {
        try {
            if (!$user || !$user->email) {
                return;
            }

            $subject = $this->buildBilingualForUser(
                $user,
                'welcome_subject',
                'Welcome to Netflare – unlock your internet in 2 steps',
                '欢迎来到 Netflare · 两步开启你的高速网络',
                []
            );

            $content = $this->buildWelcomeEmail($user);

            $this->sendEmail($user, $user->email, $subject, $content, null, null);

            Log::info('UserMailer: Sent welcome email', ['user_id' => $user->id, 'email' => $user->email]);
        } catch (\Exception $e) {
            Log::error('UserMailer: Failed to send welcome email', ['error' => $e->getMessage()]);
        }
    }

    public function handleOrderCreated($order): void
    {
        try {
            $user = User::find($order->user_id);
            if (!$user || !$user->email) {
                return;
            }

            $plan = Plan::find($order->plan_id);

            $subject = $this->buildBilingualForUser(
                $user,
                'order_created_subject',
                "Wait, wait – don’t miss today’s huge discount on your plan",
                "等等！你差一点就错过今天这笔大优惠了",
                []
            );

            $content = $this->buildOrderCreatedEmail($order, $user, $plan ? $plan->name : 'Your plan');

            $this->sendEmail($user, $user->email, $subject, $content, $order, $plan);

            Log::info('UserMailer: Sent order created email', ['order_id' => $order->id, 'email' => $user->email]);
        } catch (\Exception $e) {
            Log::error('UserMailer: Failed to send order created email', ['error' => $e->getMessage()]);
        }
    }

    public function handleOrderPaid($order): void
    {
        try {
            $cacheKey = "usermailer:order_paid:{$order->id}";
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, true, 600);

            $user = User::find($order->user_id);
            if (!$user || !$user->email) {
                return;
            }

            $plan = Plan::find($order->plan_id);

            $subjectPaid = $this->buildBilingualForUser(
                $user,
                'order_paid_subject',
                "You’re in – your Netflare access is live",
                "欢迎加入 Netflare · 你的服务已激活",
                []
            );
            $contentPaid = $this->buildOrderPaidEmail($order, $user, $plan ? $plan->name : 'Subscription');
            $this->sendEmail($user, $user->email, $subjectPaid, $contentPaid, $order, $plan);

            if ($this->getConfig('send_thank_you', true)) {
                $subjectThanks = $this->buildBilingualForUser(
                    $user,
                    'thank_you_subject',
                    "Thank you for staying with us",
                    "感谢你的选择与信任",
                    []
                );
                $contentThanks = $this->buildThankYouEmail($user);
                $this->sendEmail($user, $user->email, $subjectThanks, $contentThanks, $order, $plan);
            }

            if ($this->getConfig('send_getting_started', true)) {
                $subjectGuide = $this->buildBilingualForUser(
                    $user,
                    'getting_started_subject',
                    "How to start using your subscription",
                    "4 个步骤快速用起来",
                    []
                );
                $contentGuide = $this->buildGettingStartedEmail($user);
                $this->sendEmail($user, $user->email, $subjectGuide, $contentGuide, $order, $plan);
            }

            Log::info('UserMailer: Sent order paid emails', ['order_id' => $order->id, 'email' => $user->email]);
        } catch (\Exception $e) {
            Log::error('UserMailer: Failed to send order paid emails', ['error' => $e->getMessage()]);
        }
    }

    public function handleOrderCancelled($order): void
    {
        try {
            if (!$this->getConfig('send_reactivation_email', true)) {
                return;
            }

            $cacheKey = "usermailer:reactivation:{$order->id}";
            if (Cache::has($cacheKey)) {
                return;
            }
            Cache::put($cacheKey, true, 86400);

            $user = User::find($order->user_id);
            if (!$user || !$user->email) {
                return;
            }

            $plan = Plan::find($order->plan_id);

            $subject = $this->buildBilingualForUser(
                $user,
                'reactivation_subject',
                "We saw you left – before you go for good, open this",
                "在你彻底离开 Netflare 之前，这封邮件值得你看 30 秒",
                []
            );

            $content = $this->buildReactivationEmail($order, $user, $plan ? $plan->name : 'Your plan');

            $this->sendEmail($user, $user->email, $subject, $content, $order, $plan);

            Log::info('UserMailer: Sent reactivation email', ['order_id' => $order->id, 'email' => $user->email]);
        } catch (\Exception $e) {
            Log::error('UserMailer: Failed to send reactivation email', ['error' => $e->getMessage()]);
        }
    }

    /* ========== UNPAID REMINDERS ========== */

    public function sendUnpaidReminders(): void
    {
        try {
            $reminderHours = array_map('intval', explode(',', $this->getConfig('reminder_hours', '1,6,24')));

            foreach ($reminderHours as $hours) {
                $this->sendReminderForHours($hours);
            }
        } catch (\Exception $e) {
            Log::error('UserMailer: Failed to send unpaid reminders', ['error' => $e->getMessage()]);
        }
    }

    protected function sendReminderForHours(int $hours): void
    {
        $minTime = time() - ($hours * 3600) - 900;
        $maxTime = time() - ($hours * 3600) + 900;

        $orders = Order::where('status', 0)
            ->whereRaw('created_at >= ? AND created_at <= ?', [$minTime, $maxTime])
            ->get();

        foreach ($orders as $order) {
            $cacheKey = "usermailer:reminder:{$order->id}:{$hours}h";
            if (Cache::has($cacheKey)) {
                continue;
            }

            $user = User::find($order->user_id);
            if (!$user || !$user->email) {
                continue;
            }

            $plan = Plan::find($order->plan_id);

            $subject = $this->buildBilingualForUser(
                $user,
                'reminder_subject',
                "Still open: complete your Netflare order in one click",
                "你的 Netflare 订单还在等你，一键即可完成",
                []
            );
            $content = $this->buildReminderEmail($order, $user, $plan ? $plan->name : 'Your plan', $hours);

            $this->sendEmail($user, $user->email, $subject, $content, $order, $plan);

            Cache::put($cacheKey, true, 86400);
            Log::info('UserMailer: Sent unpaid reminder', ['order_id' => $order->id, 'hours' => $hours]);
        }
    }

    /* ========== SEND EMAIL (ADD FOOTER) ========== */

    protected function sendEmail(
        ?User $user,
        string $to,
        string $subject,
        string $content,
        ?Order $order = null,
        ?Plan $plan = null
    ): void {
        $siteName = $this->getConfig('site_name', 'Netflare');
        $dashboardUrl = $this->getConfig('dashboard_url', 'https://www.netflare.co/console');

        $footer = $this->buildContactFooter($order, $plan, $user);
        $fullContent = rtrim($content) . $footer;

        dispatch(new SendEmailJob([
            'email' => $to,
            'subject' => "[{$siteName}] {$subject}",
            'template_name' => 'notify',
            'template_value' => [
                'name' => 'Customer',
                'content' => $fullContent,
                'url' => $dashboardUrl,
            ]
        ]));
    }

    /* ========== CONTENT BUILDERS ========== */

    protected function buildWelcomeEmail($user): string
    {
        $plansUrl     = $this->getConfig('plans_url', $this->getConfig('dashboard_url', 'https://www.netflare.co/console'));
        $knowledgeUrl = $this->getConfig('knowledge_url', 'https://www.netflare.co/console/knowledge');

        return $this->buildBilingualContentForUser(
            $user,
            'welcome_content',
            // defaults already defined in config.json; we just mirror for safety
            "Welcome onboard!\n\nWe’re excited to have you here.\n\nAs a new member, you’ll enjoy fast, secure and privacy-friendly service.\n\nQuick start:\n1. Login to your dashboard\n2. Choose a plan that fits you\n3. Download the client app\n4. Import subscription and connect\n\nDashboard: {dashboard_url}",
            "欢迎加入！\n\n很高兴在这里遇见你。\n\n从现在起，你将享受到更稳定、更安全、更自由的上网体验。\n\n快速上手：\n1. 登录用户面板\n2. 选择适合自己的订阅套餐\n3. 下载对应设备的客户端\n4. 导入订阅并一键连接\n\n进入面板：{dashboard_url}",
            [
                '{plans_url}'     => $plansUrl,
                '{knowledge_url}' => $knowledgeUrl,
            ]
        );
    }

    protected function buildOrderCreatedEmail($order, $user, string $planName): string
    {
        $dashboardUrl = $this->getConfig('dashboard_url', 'https://www.netflare.co/console');

        return $this->buildBilingualContentForUser(
            $user,
            'order_created_content',
            "Hi,\n\nYou just created an order with Netflare – great choice.\n\nBut before you pay, there’s something you should know:\n\n🎯 Today’s special: you’re eligible for a huge discount on the yearly version of your selected plan.\n\nPlan: {plan_name}\nToday’s coupon: NF25OFFTODAY\nOffer: big discount on yearly (or longer) for this plan – today only.\n\nUse this at checkout now and lock in a lower price before today ends.\n\nIf you have already paid for a shorter term, reply to this email or contact us via the footer and ask to upgrade – tell us your email and plan, and we’ll help you switch and still apply today’s discount.\n\nGo to dashboard: {dashboard_url}\n",
            "你好，\n\n你刚刚在 Netflare 创建了一笔订单，这是一个非常好的开始。\n\n在你付款之前，有件事你一定要知道：\n\n🎯 今天限定：你当前选择的套餐，可以享受「年付大额折扣」。\n\n套餐：{plan_name}\n今日专属优惠码：NF25OFFTODAY\n权益：将该套餐升级为年付（或更长期）可享受大幅折扣，仅限今天。\n\n现在在结算页面输入优惠码，就能在今天之内锁定更低价格。\n\n如果你已经为较短时长的套餐完成了付款，也没关系——只要在今天内回复本邮件，或通过邮件底部的任何联系方式联系我们，告诉我们你的账号和套餐，我们会协助你升级到年付并尽量为你保留今天的优惠。\n\n前往面板：{dashboard_url}\n",
            [
                '{plan_name}'     => $planName,
                '{dashboard_url}' => $dashboardUrl,
            ]
        );
    }

    protected function buildOrderPaidEmail($order, $user, string $planName): string
    {
        $dashboardUrl = $this->getConfig('dashboard_url', 'https://www.netflare.co/console');

        return $this->buildBilingualContentForUser(
            $user,
            'order_paid_content',
            "Hi,\n\nThank you for choosing Netflare – your payment is confirmed and your access is now live.\n\n🚀 What you can do next\n- Login to your dashboard\n- Get your subscription link\n- Import it into your favorite client and connect\n\nDashboard: {dashboard_url}\n\n💎 Limited upgrade offer (today only)\nIf you upgrade to a yearly or 2‑year plan now, you can get an extra 20% OFF.\n\nExclusive coupon: NF15\nApplies to: yearly and 2‑year upgrades\nBilling is pro‑rated – we only charge you for the remaining difference, not from zero.\n\nIf you want to upgrade, simply place an upgrade order and use NF15 at checkout. If something looks confusing, reply to this email and we’ll walk you through it.\n\n🤝 Invite friends, earn 20% forever\nYou now have a referral link inside your dashboard.\n- Share it with friends\n- Every time they pay, you earn 20% flat commission\n\nYou can withdraw or use it towards your own renewals. It’s one of the easiest ways to make your Netflare subscription free.\n\nWelcome again,\nNetflare Team",
            "你好，\n\n感谢你选择 Netflare，你的付款已经确认，服务已成功开通。\n\n🚀 现在你可以这样做\n- 登录用户面板\n- 获取你的专属订阅链接\n- 在你常用的客户端中导入并连接\n\n用户面板：{dashboard_url}\n\n💎 今日限定升级福利\n如果你现在将订阅升级为「年付」或「两年付」，我们额外赠送 20% 折扣。\n\n专属升级优惠码：NF15\n适用范围：年付 / 两年付套餐升级\n计费方式：按剩余时长差价计算，不会重复收取已使用部分。\n\n你可以直接在面板中发起升级订单并在结算时输入 NF15。如果升级流程有任何疑问，只需要回复本邮件，我们会一步步帮你处理。\n\n🤝 邀请好友，终身 20% 返佣\n现在你的面板中已经有专属邀请链接：\n- 把它分享给朋友\n- 他们每次付费，你都可获得 20% 返佣\n\n返佣可以提现，也可以抵扣你自己的续费成本，让订阅“自己养活自己”。\n\n欢迎加入 Netflare，\nNetflare 团队",
            [
                '{dashboard_url}' => $dashboardUrl,
            ]
        );
    }

    protected function buildThankYouEmail($user): string
    {
        // 内容已在 config 中优化，这里只简单调用
        $dashboardUrl = $this->getConfig('dashboard_url', 'https://www.netflare.co/console');
        $commission   = $this->getConfig('referral_commission', '20');

        return $this->buildBilingualContentForUser(
            $user,
            'thank_you_content',
            $this->getConfig('thank_you_content', ''),
            $this->getConfig('thank_you_content_zh', ''),
            [
                '{referral_commission}' => $commission,
                '{dashboard_url}'       => $dashboardUrl,
            ]
        );
    }

    protected function buildGettingStartedEmail($user): string
    {
        $dashboardUrl = $this->getConfig('dashboard_url', 'https://www.netflare.co/console');
        $knowledgeUrl = $this->getConfig('knowledge_url', 'https://www.netflare.co/console/knowledge');
        $ticketUrl    = $this->getConfig('ticket_url', 'https://www.netflare.co/console/ticket');

        return $this->buildBilingualContentForUser(
            $user,
            'getting_started_content',
            $this->getConfig('getting_started_content', ''),
            $this->getConfig('getting_started_content_zh', ''),
            [
                '{dashboard_url}' => $dashboardUrl,
                '{knowledge_url}' => $knowledgeUrl,
                '{ticket_url}'    => $ticketUrl,
            ]
        );
    }

    protected function buildReminderEmail($order, $user, string $planName, int $hours): string
    {
        $dashboardUrl = $this->getConfig('dashboard_url', 'https://www.netflare.co/console');

        return $this->buildBilingualContentForUser(
            $user,
            'reminder_content',
            $this->getConfig('reminder_content', ''),
            $this->getConfig('reminder_content_zh', ''),
            [
                '{dashboard_url}' => $dashboardUrl,
            ]
        );
    }

    protected function buildReactivationEmail($order, $user, string $planName): string
    {
        $couponCode     = $this->getConfig('reactivation_coupon_code', 'COMEBACK20');
        $couponDiscount = $this->getConfig('reactivation_coupon_discount', '20');

        return $this->buildBilingualContentForUser(
            $user,
            'reactivation_content',
            $this->getConfig('reactivation_content', ''),
            $this->getConfig('reactivation_content_zh', ''),
            [
                '{coupon_code}'     => $couponCode,
                '{coupon_discount}' => $couponDiscount,
            ]
        );
    }
}