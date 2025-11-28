<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Jobs\SendTelegramJob;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Simple Notification Service for V2Board
 * 
 * Sends notifications to:
 * - Admin Email
 * - Telegram (admin chat)
 * - Discord Webhook
 * - User Email (default alerts)
 */
class NotificationService
{
    /**
     * Send notification when user registers
     */
    public static function userRegistered(User $user): void
    {
        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');

        // Send welcome email to user
        if (config('v2board.notify_user_welcome', true)) {
            self::sendUserEmail($user, 
                __('Welcome to :site_name', ['site_name' => $siteName]),
                __("Hello!\n\nWelcome to :site_name.\n\nYou can now login to your dashboard and choose a plan.\n\nDashboard: :url", [
                    'site_name' => $siteName,
                    'url' => $siteUrl
                ])
            );
        }

        // Send admin notification
        if (config('v2board.notify_admin_new_user', true)) {
            $message = "🆕 New User Registration\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📧 Email: {$user->email}\n";
            $message .= "🕐 Time: " . date('Y-m-d H:i:s');

            self::sendAdminNotification('New User Registration', $message);
        }
    }

    /**
     * Send notification when order is created
     */
    public static function orderCreated(Order $order): void
    {
        $user = User::find($order->user_id);
        if (!$user) return;

        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');

        // Send email to user about pending order
        if (config('v2board.notify_user_order_created', true)) {
            $amount = number_format($order->total_amount / 100, 2);
            self::sendUserEmail($user,
                __('Order Created - :site_name', ['site_name' => $siteName]),
                __("Your order has been created.\n\nOrder No: :trade_no\nAmount: :amount\n\nPlease complete payment to activate your subscription.\n\nDashboard: :url", [
                    'trade_no' => $order->trade_no,
                    'amount' => $amount,
                    'url' => $siteUrl
                ])
            );
        }
    }

    /**
     * Send notification when payment is successful
     */
    public static function paymentSuccess(Order $order): void
    {
        $user = User::find($order->user_id);
        if (!$user) return;

        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');
        $amount = number_format($order->total_amount / 100, 2);

        // Send email to user about successful payment
        if (config('v2board.notify_user_payment_success', true)) {
            self::sendUserEmail($user,
                __('Payment Confirmed - :site_name', ['site_name' => $siteName]),
                __("Your payment has been confirmed and your subscription is now active.\n\nOrder No: :trade_no\nAmount: :amount\n\nYou can now login to your dashboard to get your subscription link.\n\nDashboard: :url", [
                    'trade_no' => $order->trade_no,
                    'amount' => $amount,
                    'url' => $siteUrl
                ])
            );
        }

        // Send admin notification
        if (config('v2board.notify_admin_payment', true)) {
            $message = "💰 Payment Received\n";
            $message .= "━━━━━━━━━━━━━━━━━━━━\n";
            $message .= "📧 User: {$user->email}\n";
            $message .= "💵 Amount: {$amount}\n";
            $message .= "🔢 Order: {$order->trade_no}\n";
            $message .= "🕐 Time: " . date('Y-m-d H:i:s');

            self::sendAdminNotification('Payment Received', $message);
        }
    }

    /**
     * Send notification when ticket is created
     */
    public static function ticketCreated(Ticket $ticket): void
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        // Get ticket message
        $message = TicketMessage::where('ticket_id', $ticket->id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$message) return;

        // Send admin notification
        if (config('v2board.notify_admin_ticket', true)) {
            $tgMessage = "📮 New Ticket #{$ticket->id}\n";
            $tgMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $tgMessage .= "📧 User: {$user->email}\n";
            $tgMessage .= "📝 Subject: {$ticket->subject}\n";
            $tgMessage .= "💬 Message: " . mb_substr($message->message, 0, 200);

            self::sendAdminNotification('New Ticket', $tgMessage);
        }
    }

    /**
     * Send notification when ticket is replied by user
     */
    public static function ticketReplied(Ticket $ticket, TicketMessage $message): void
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        // Send admin notification for user reply
        if (config('v2board.notify_admin_ticket', true)) {
            $tgMessage = "📮 Ticket Reply #{$ticket->id}\n";
            $tgMessage .= "━━━━━━━━━━━━━━━━━━━━\n";
            $tgMessage .= "📧 User: {$user->email}\n";
            $tgMessage .= "📝 Subject: {$ticket->subject}\n";
            $tgMessage .= "💬 Reply: " . mb_substr($message->message, 0, 200);

            self::sendAdminNotification('Ticket Reply', $tgMessage);
        }
    }

    /**
     * Send notification when admin replies to ticket
     */
    public static function ticketAdminReplied(Ticket $ticket, TicketMessage $message): void
    {
        $user = User::find($ticket->user_id);
        if (!$user) return;

        $siteName = config('v2board.app_name', 'V2Board');
        $siteUrl = config('v2board.app_url', '');

        // Send email to user about ticket reply
        if (config('v2board.notify_user_ticket_reply', true)) {
            self::sendUserEmail($user,
                __('Ticket Reply - :site_name', ['site_name' => $siteName]),
                __("Your ticket has received a reply.\n\nSubject: :subject\n\nPlease login to your dashboard to view the reply.\n\nDashboard: :url", [
                    'subject' => $ticket->subject,
                    'url' => $siteUrl
                ])
            );
        }
    }

    /**
     * Send notification to admin when server is down
     */
    public static function serverDown(string $serverName, string $serverType): void
    {
        if (!config('v2board.notify_admin_server_down', true)) return;

        $message = "⚠️ Server Alert\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🖥️ Server: {$serverName}\n";
        $message .= "📡 Type: {$serverType}\n";
        $message .= "❌ Status: DOWN\n";
        $message .= "🕐 Time: " . date('Y-m-d H:i:s');

        self::sendAdminNotification('Server Down', $message);
    }

    /**
     * Send email to user
     */
    protected static function sendUserEmail(User $user, string $subject, string $content): void
    {
        try {
            SendEmailJob::dispatch([
                'email' => $user->email,
                'subject' => $subject,
                'template_name' => 'notify',
                'template_value' => [
                    'name' => 'Customer',
                    'content' => $content,
                    'url' => config('v2board.app_url', '')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send user email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification to admin via all configured channels
     */
    public static function sendAdminNotification(string $title, string $message): void
    {
        // Send via Telegram
        self::sendTelegram($message);

        // Send via Discord
        self::sendDiscord($title, $message);

        // Send via Email to admin
        self::sendAdminEmail($title, $message);
    }

    /**
     * Send Telegram notification to admin
     */
    protected static function sendTelegram(string $message): void
    {
        $botToken = config('v2board.telegram_bot_token');
        $chatId = config('v2board.notify_telegram_chat_id');

        if (!$botToken || !$chatId) return;

        try {
            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
            Http::post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send Telegram', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send Discord webhook notification
     */
    protected static function sendDiscord(string $title, string $message): void
    {
        $webhookUrl = config('v2board.notify_discord_webhook');

        if (!$webhookUrl) return;

        try {
            Http::post($webhookUrl, [
                'embeds' => [
                    [
                        'title' => $title,
                        'description' => str_replace(['━', '📮', '💰', '📧', '📝', '💬', '🆕', '💵', '🔢', '🕐', '⚠️', '🖥️', '📡', '❌'], '', $message),
                        'color' => 3447003,
                        'timestamp' => date('c')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send Discord', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send email to admin
     */
    protected static function sendAdminEmail(string $title, string $message): void
    {
        $adminEmail = config('v2board.notify_admin_email');

        if (!$adminEmail) return;

        try {
            SendEmailJob::dispatch([
                'email' => $adminEmail,
                'subject' => "[Admin] {$title}",
                'template_name' => 'notify',
                'template_value' => [
                    'name' => 'Admin',
                    'content' => str_replace(['━', '📮', '💰', '📧', '📝', '💬', '🆕', '💵', '🔢', '🕐', '⚠️', '🖥️', '📡', '❌'], '', $message),
                    'url' => config('v2board.app_url', '')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('NotificationService: Failed to send admin email', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
