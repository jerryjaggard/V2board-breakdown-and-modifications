<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * V2Board 通知服务
 * 
 * 简单易用的通知系统，支持:
 * - Telegram 机器人通知管理员
 * - Discord Webhook 通知
 * - 邮件通知管理员
 * - 用户邮件通知（中文默认内容）
 * 
 * 配置项（在 v2board 配置中设置）:
 * - telegram_bot_token: Telegram 机器人 Token
 * - notify_telegram_chat_id: Telegram 接收通知的 Chat ID
 * - notify_discord_webhook: Discord Webhook URL
 * - notify_admin_email: 管理员邮箱
 */
class NotificationService
{
    /**
     * 用户注册后发送通知
     */
    public static function userRegistered(User $user): void
    {
        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');

        // 发送欢迎邮件给用户
        self::sendUserWelcomeEmail($user, $siteName, $siteUrl);

        // 发送管理员通知
        $message = "🎉 新用户注册\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📧 邮箱: {$user->email}\n";
        $message .= "🕐 时间: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('新用户注册', $message);
    }

    /**
     * 订单创建后发送通知
     */
    public static function orderCreated(Order $order): void
    {
        $user = User::find($order->user_id);
        if (!$user) return;

        $plan = Plan::find($order->plan_id);
        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');
        $amount = number_format($order->total_amount / 100, 2);

        // 发送订单创建邮件给用户
        self::sendOrderCreatedEmail($user, $order, $plan, $siteName, $siteUrl);

        // 不需要通知管理员创建订单（避免过多通知）
    }

    /**
     * 支付成功后发送通知
     */
    public static function paymentSuccess(Order $order): void
    {
        $user = User::find($order->user_id);
        if (!$user) return;

        $plan = Plan::find($order->plan_id);
        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');
        $amount = number_format($order->total_amount / 100, 2);
        $planName = $plan ? $plan->name : '套餐';

        // 发送支付成功邮件给用户
        self::sendPaymentSuccessEmail($user, $order, $plan, $siteName, $siteUrl);

        // 发送管理员通知
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
     * 工单创建后发送通知
     */
    public static function ticketCreated(Ticket $ticket): void
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        // 获取工单内容
        $ticketMessage = TicketMessage::where('ticket_id', $ticket->id)
            ->orderBy('id', 'asc')
            ->first();
        
        $content = $ticketMessage ? mb_substr($ticketMessage->message, 0, 200) : '无内容';

        // 发送管理员通知
        $message = "📮 新工单 #{$ticket->id}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📧 用户: {$user->email}\n";
        $message .= "📝 主题: {$ticket->subject}\n";
        $message .= "💬 内容: {$content}\n";
        $message .= "🕐 时间: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('新工单', $message);
    }

    /**
     * 用户回复工单后发送通知
     */
    public static function ticketReplied(Ticket $ticket, TicketMessage $message): void
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        $content = mb_substr($message->message, 0, 200);

        // 发送管理员通知
        $adminMessage = "📮 工单回复 #{$ticket->id}\n";
        $adminMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
        $adminMessage .= "📧 用户: {$user->email}\n";
        $adminMessage .= "📝 主题: {$ticket->subject}\n";
        $adminMessage .= "💬 回复: {$content}\n";
        $adminMessage .= "🕐 时间: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('工单回复', $adminMessage);
    }

    /**
     * 管理员回复工单后发送通知给用户
     */
    public static function ticketAdminReplied(Ticket $ticket, TicketMessage $message): void
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');

        self::sendTicketReplyEmail($user, $ticket, $siteName, $siteUrl);
    }

    // ==================== 用户邮件（中文默认内容）====================

    /**
     * 发送欢迎邮件
     */
    protected static function sendUserWelcomeEmail(User $user, string $siteName, string $siteUrl): void
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

            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => "欢迎加入 {$siteName}",
                'template_name' => 'notify',
                'template_value' => [
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: 发送欢迎邮件失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 发送订单创建邮件
     */
    protected static function sendOrderCreatedEmail(User $user, Order $order, ?Plan $plan, string $siteName, string $siteUrl): void
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

            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => "订单待支付 - {$siteName}",
                'template_name' => 'notify',
                'template_value' => [
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: 发送订单邮件失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 发送支付成功邮件
     */
    protected static function sendPaymentSuccessEmail(User $user, Order $order, ?Plan $plan, string $siteName, string $siteUrl): void
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

            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => "支付成功 - {$siteName}",
                'template_name' => 'notify',
                'template_value' => [
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: 发送支付成功邮件失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 发送工单回复邮件
     */
    protected static function sendTicketReplyEmail(User $user, Ticket $ticket, string $siteName, string $siteUrl): void
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

            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => "工单回复 - {$siteName}",
                'template_name' => 'notify',
                'template_value' => [
                    'name' => '用户',
                    'content' => $content,
                    'url' => $siteUrl
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: 发送工单回复邮件失败', ['error' => $e->getMessage()]);
        }
    }

    // ==================== 管理员通知 ====================

    /**
     * 发送管理员通知（Telegram、Discord、邮件）
     */
    public static function sendAdminNotification(string $title, string $message): void
    {
        self::sendTelegram($message);
        self::sendDiscord($title, $message);
        self::sendAdminEmail($title, $message);
    }

    /**
     * 发送 Telegram 通知
     */
    protected static function sendTelegram(string $message): void
    {
        $botToken = config('v2board.telegram_bot_token');
        $chatId = config('v2board.notify_telegram_chat_id');

        if (!$botToken || !$chatId) return;

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            Http::timeout(10)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: Telegram 发送失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 发送 Discord Webhook 通知
     */
    protected static function sendDiscord(string $title, string $message): void
    {
        $webhookUrl = config('v2board.notify_discord_webhook');

        if (!$webhookUrl) return;

        try {
            // 移除表情符号用于 Discord embed
            $cleanMessage = preg_replace('/[^\x20-\x7E\x{4E00}-\x{9FFF}\n:]/u', '', $message);
            
            Http::timeout(10)->post($webhookUrl, [
                'embeds' => [
                    [
                        'title' => $title,
                        'description' => $cleanMessage,
                        'color' => 3447003,
                        'timestamp' => date('c')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: Discord 发送失败', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 发送管理员邮件
     */
    protected static function sendAdminEmail(string $title, string $message): void
    {
        $adminEmail = config('v2board.notify_admin_email');
        $siteName = config('v2board.app_name', 'V2Board');

        if (!$adminEmail) return;

        try {
            // 移除表情符号用于邮件
            $cleanMessage = preg_replace('/[^\x20-\x7E\x{4E00}-\x{9FFF}\n:]/u', '', $message);

            SendEmailJob::dispatch([
                'email' => $adminEmail,
                'subject' => "[{$siteName}] {$title}",
                'template_name' => 'notify',
                'template_value' => [
                    'name' => '管理员',
                    'content' => $cleanMessage,
                    'url' => config('v2board.app_url', '')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: 管理员邮件发送失败', ['error' => $e->getMessage()]);
        }
    }
}
