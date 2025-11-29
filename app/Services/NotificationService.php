<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * V2Board Notification Service
 * 
 * Simple notification system for V2Board:
 * - Telegram bot notifications to admin
 * - Discord webhook notifications
 * - Email notifications to admin
 * - User email notifications (Chinese content)
 * 
 * Configuration (use .env file, no database access needed):
 * 
 * Add to .env file:
 * V2BOARD_TELEGRAM_BOT_TOKEN=Your Bot Token
 * V2BOARD_NOTIFY_TELEGRAM_CHAT_ID=Your Chat ID
 * V2BOARD_NOTIFY_DISCORD_WEBHOOK=Discord Webhook URL
 * V2BOARD_NOTIFY_ADMIN_EMAIL=admin@example.com
 */
class NotificationService
{
    /**
     * Get config value (priority: .env, then database)
     */
    protected static function getConfig($key, $default = null)
    {
        $envMapping = array(
            'telegram_bot_token' => 'V2BOARD_TELEGRAM_BOT_TOKEN',
            'notify_telegram_chat_id' => 'V2BOARD_NOTIFY_TELEGRAM_CHAT_ID',
            'notify_discord_webhook' => 'V2BOARD_NOTIFY_DISCORD_WEBHOOK',
            'notify_admin_email' => 'V2BOARD_NOTIFY_ADMIN_EMAIL',
            'app_name' => 'V2BOARD_APP_NAME',
            'app_url' => 'V2BOARD_APP_URL',
        );

        if (isset($envMapping[$key])) {
            $envValue = env($envMapping[$key]);
            if ($envValue !== null && $envValue !== '') {
                return $envValue;
            }
        }

        return config("v2board.{$key}", $default);
    }

    /**
     * User registered notification
     */
    public static function userRegistered(User $user)
    {
        $siteName = self::getConfig('app_name', 'V2Board');
        $siteUrl = self::getConfig('app_url', '');

        // Send welcome email to user
        self::sendUserWelcomeEmail($user, $siteName, $siteUrl);

        // Send admin notification
        $message = "🎉 新用户注册\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📧 邮箱: {$user->email}\n";
        $message .= "🕐 时间: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('新用户注册', $message);
    }

    /**
     * Order created notification
     */
    public static function orderCreated(Order $order)
    {
        $user = User::find($order->user_id);
        if (!$user) return;

        $plan = Plan::find($order->plan_id);
        $siteName = self::getConfig('app_name', 'V2Board');
        $siteUrl = self::getConfig('app_url', '');

        // Send order created email to user
        self::sendOrderCreatedEmail($user, $order, $plan, $siteName, $siteUrl);
    }

    /**
     * Payment success notification
     */
    public static function paymentSuccess(Order $order)
    {
        $user = User::find($order->user_id);
        if (!$user) return;

        $plan = Plan::find($order->plan_id);
        $siteName = self::getConfig('app_name', 'V2Board');
        $siteUrl = self::getConfig('app_url', '');
        $amount = number_format($order->total_amount / 100, 2);
        $planName = $plan ? $plan->name : '套餐';

        // Send payment success email to user
        self::sendPaymentSuccessEmail($user, $order, $plan, $siteName, $siteUrl);

        // Send admin notification
        $message = "💰 收到新付款\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📧 用户: {$user->email}\n";
        $message .= "💵 金额: ¥{$amount}\n";
        $message .= "📦 套餐: {$planName}\n";
        $message .= "🔢 订单: {$order->trade_no}\n";
        $message .= "🕐 时间: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('收到新付款', $message);
    }

    /**
     * Ticket created notification
     */
    public static function ticketCreated(Ticket $ticket, $content = '')
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        if (empty($content)) {
            $ticketMessage = TicketMessage::where('ticket_id', $ticket->id)
                ->orderBy('id', 'asc')
                ->first();
            $content = $ticketMessage ? mb_substr($ticketMessage->message, 0, 200) : '无内容';
        }

        // Send admin notification
        $message = "📮 新工单 #{$ticket->id}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📧 用户: {$user->email}\n";
        $message .= "📝 主题: {$ticket->subject}\n";
        $message .= "💬 内容: {$content}\n";
        $message .= "🕐 时间: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('新工单', $message);
    }

    /**
     * User replied ticket notification
     */
    public static function ticketReplied(Ticket $ticket, TicketMessage $ticketMessage)
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        $content = mb_substr($ticketMessage->message, 0, 200);

        // Send admin notification
        $adminMessage = "📮 工单回复 #{$ticket->id}\n";
        $adminMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
        $adminMessage .= "📧 用户: {$user->email}\n";
        $adminMessage .= "📝 主题: {$ticket->subject}\n";
        $adminMessage .= "💬 回复: {$content}\n";
        $adminMessage .= "🕐 时间: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('工单回复', $adminMessage);
    }

    /**
     * Admin replied ticket - send email to user
     */
    public static function ticketAdminReplied(Ticket $ticket, TicketMessage $ticketMessage)
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        $siteName = self::getConfig('app_name', 'V2Board');
        $siteUrl = self::getConfig('app_url', '');

        self::sendTicketReplyEmail($user, $ticket, $siteName, $siteUrl);
    }

    // ==================== User Emails (Chinese content) ====================

    protected static function sendUserWelcomeEmail(User $user, $siteName, $siteUrl)
    {
        try {
            $content = "您好！\n\n";
            $content .= "欢迎加入 {$siteName}！\n\n";
            $content .= "您的账号已成功创建。现在您可以登录用户面板，选择适合您的订阅套餐。\n\n";
            $content .= "快速开始：\n";
            $content .= "1. 登录用户面板\n";
            $content .= "2. 选择订阅套餐\n";
            $content .= "3. 完成支付\n";
            $content .= "4. 下载客户端并导入订阅\n\n";
            $content .= "用户面板: {$siteUrl}\n\n";
            $content .= "如有任何问题，请通过工单系统联系我们。\n\n";
            $content .= "{$siteName} 团队";

            SendEmailJob::dispatch(array(
                'email' => $user->email,
                'subject' => "欢迎加入 {$siteName}",
                'template_name' => 'notify',
                'template_value' => array(
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                )
            ));
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send welcome email', array('error' => $e->getMessage()));
        }
    }

    protected static function sendOrderCreatedEmail(User $user, Order $order, $plan, $siteName, $siteUrl)
    {
        try {
            $amount = number_format($order->total_amount / 100, 2);
            $planName = $plan ? $plan->name : '套餐';

            $content = "您好！\n\n";
            $content .= "您在 {$siteName} 创建了一个新订单。\n\n";
            $content .= "订单详情：\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━\n";
            $content .= "订单号: {$order->trade_no}\n";
            $content .= "套餐: {$planName}\n";
            $content .= "金额: ¥{$amount}\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━\n\n";
            $content .= "请尽快完成支付以激活您的订阅。\n\n";
            $content .= "前往支付: {$siteUrl}\n\n";
            $content .= "{$siteName} 团队";

            SendEmailJob::dispatch(array(
                'email' => $user->email,
                'subject' => "订单待支付 - {$siteName}",
                'template_name' => 'notify',
                'template_value' => array(
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                )
            ));
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send order email', array('error' => $e->getMessage()));
        }
    }

    protected static function sendPaymentSuccessEmail(User $user, Order $order, $plan, $siteName, $siteUrl)
    {
        try {
            $amount = number_format($order->total_amount / 100, 2);
            $planName = $plan ? $plan->name : '套餐';

            $content = "您好！\n\n";
            $content .= "感谢您的支付！您的订阅已成功激活。\n\n";
            $content .= "订单详情：\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━\n";
            $content .= "订单号: {$order->trade_no}\n";
            $content .= "套餐: {$planName}\n";
            $content .= "金额: ¥{$amount}\n";
            $content .= "状态: ✅ 已支付\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━\n\n";
            $content .= "现在您可以：\n";
            $content .= "1. 登录用户面板获取订阅链接\n";
            $content .= "2. 下载客户端应用\n";
            $content .= "3. 导入订阅并开始使用\n\n";
            $content .= "用户面板: {$siteUrl}\n\n";
            $content .= "如有任何问题，请随时联系我们。\n\n";
            $content .= "{$siteName} 团队";

            SendEmailJob::dispatch(array(
                'email' => $user->email,
                'subject' => "支付成功 - {$siteName}",
                'template_name' => 'notify',
                'template_value' => array(
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                )
            ));
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send payment email', array('error' => $e->getMessage()));
        }
    }

    protected static function sendTicketReplyEmail(User $user, Ticket $ticket, $siteName, $siteUrl)
    {
        try {
            $content = "您好！\n\n";
            $content .= "您的工单收到了新的回复。\n\n";
            $content .= "工单详情：\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━\n";
            $content .= "工单ID: #{$ticket->id}\n";
            $content .= "主题: {$ticket->subject}\n";
            $content .= "━━━━━━━━━━━━━━━━━━━━\n\n";
            $content .= "请登录用户面板查看完整回复内容。\n\n";
            $content .= "查看工单: {$siteUrl}\n\n";
            $content .= "{$siteName} 团队";

            SendEmailJob::dispatch(array(
                'email' => $user->email,
                'subject' => "工单回复 - {$siteName}",
                'template_name' => 'notify',
                'template_value' => array(
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                )
            ));
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send ticket reply email', array('error' => $e->getMessage()));
        }
    }

    // ==================== Admin Notifications ====================

    public static function sendAdminNotification($title, $message)
    {
        self::sendTelegram($message);
        self::sendDiscord($title, $message);
        self::sendAdminEmail($title, $message);
    }

    protected static function sendTelegram($message)
    {
        $botToken = self::getConfig('telegram_bot_token');
        $chatId = self::getConfig('notify_telegram_chat_id');

        if (!$botToken || !$chatId) return;

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => ''
            )));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            Log::error('NotificationService: Telegram send failed', array('error' => $e->getMessage()));
        }
    }

    protected static function sendDiscord($title, $message)
    {
        $webhookUrl = self::getConfig('notify_discord_webhook');

        if (!$webhookUrl) return;

        try {
            $cleanMessage = preg_replace('/[^\x20-\x7E\x{4E00}-\x{9FFF}\n:]/u', '', $message);
            
            $data = json_encode(array(
                'embeds' => array(
                    array(
                        'title' => $title,
                        'description' => $cleanMessage,
                        'color' => 3447003,
                        'timestamp' => date('c')
                    )
                )
            ));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            Log::error('NotificationService: Discord send failed', array('error' => $e->getMessage()));
        }
    }

    protected static function sendAdminEmail($title, $message)
    {
        $adminEmail = self::getConfig('notify_admin_email');
        $siteName = self::getConfig('app_name', 'V2Board');

        if (!$adminEmail) return;

        try {
            $cleanMessage = preg_replace('/[^\x20-\x7E\x{4E00}-\x{9FFF}\n:]/u', '', $message);

            SendEmailJob::dispatch(array(
                'email' => $adminEmail,
                'subject' => "[{$siteName}] {$title}",
                'template_name' => 'notify',
                'template_value' => array(
                    'name' => '管理员',
                    'content' => $cleanMessage,
                    'url' => self::getConfig('app_url', '')
                )
            ));
        } catch (\Exception $e) {
            Log::error('NotificationService: Admin email send failed', array('error' => $e->getMessage()));
        }
    }
}
