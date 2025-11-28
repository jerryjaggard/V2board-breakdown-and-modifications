# V2Board Plugin Integration Guide

This guide explains how to manually integrate the 5 XBoard plugins into V2Board if the automatic plugin system isn't working for your setup.

## Prerequisites

1. V2Board installed and running
2. Database access (MySQL)
3. File access to V2Board installation directory
4. Basic understanding of PHP and Laravel

---

## Method 1: Using the Plugin Dashboard (Recommended)

### Access the Plugin Dashboard

1. Login to your V2Board admin panel at `https://your-domain/{secure_path}`
2. Navigate to `https://your-domain/{secure_path}/plugins`
3. If prompted for authentication, get your auth token:
   - Open browser DevTools (F12)
   - Go to Console tab
   - Run: `localStorage.getItem('auth_data')`
   - Copy and paste the returned value into the auth prompt

### Install Plugins

1. Click "Install" on any available plugin
2. Click "Enable" to activate it
3. Click "⚙️ Config" to configure the plugin settings

---

## Method 2: Manual Database Integration

If the plugin system doesn't work, you can manually add the plugin hooks to V2Board code.

### Step 1: Run Database Migration

Execute this SQL to create the plugins table:

```sql
CREATE TABLE IF NOT EXISTS `v2_plugins` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(100) NOT NULL UNIQUE,
  `name` varchar(100) NOT NULL,
  `version` varchar(20) DEFAULT '1.0.0',
  `type` varchar(50) DEFAULT 'feature',
  `is_enabled` tinyint(1) DEFAULT 0,
  `config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Method 3: Direct Code Integration (No Plugin System)

If you want the plugin functionality without the plugin system, integrate each feature directly.

### Telegram Plugin Integration

The Telegram plugin provides:
- User account binding via Telegram bot
- Ticket notifications to admin
- Payment success notifications
- Bot commands: /start, /bind, /traffic, /getlatesturl, /unbind

**Step 1: Add User Telegram ID Field (if not exists)**

```sql
ALTER TABLE `v2_user` ADD COLUMN `telegram_id` bigint(20) DEFAULT NULL;
```

**Step 2: Create TelegramBotCommand**

Create file: `app/Console/Commands/TelegramBot.php`

```php
<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Ticket;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramBot extends Command
{
    protected $signature = 'telegram:commands';
    protected $description = 'Handle Telegram bot commands';

    public function handle()
    {
        $telegramService = new TelegramService();
        
        // Register your commands with Telegram
        $commands = [
            ['command' => 'start', 'description' => '开始使用'],
            ['command' => 'bind', 'description' => '绑定账号'],
            ['command' => 'traffic', 'description' => '查看流量'],
            ['command' => 'getlatesturl', 'description' => '获取订阅链接'],
            ['command' => 'unbind', 'description' => '解绑账号'],
        ];
        
        $telegramService->setBotCommands($commands);
        
        $this->info('Telegram bot commands registered successfully.');
    }
}
```

**Step 3: Add Telegram Notifications to Services**

In `app/Services/OrderService.php`, add payment notification:

```php
// After successful payment processing
$telegramService = new \App\Services\TelegramService();
$message = sprintf(
    "💰成功收款%s元\n" .
    "———————————————\n" .
    "支付接口：%s\n" .
    "本站订单：`%s`",
    $order->total_amount / 100,
    $order->payment->name ?? 'Unknown',
    $order->trade_no
);
$telegramService->sendMessageWithAdmin($message, true);
```

In `app/Services/TicketService.php`, add ticket notification:

```php
// After ticket creation or reply
$telegramService = new \App\Services\TelegramService();
$user = User::find($ticket->user_id);
$message = "📮 工单提醒 #{$ticket->id}\n";
$message .= "━━━━━━━━━━━━━━━━━━━━\n";
$message .= "📧 邮箱: {$user->email}\n";
$message .= "📝 主题: {$ticket->subject}\n";
$telegramService->sendMessageWithAdmin($message, true);
```

---

### NotifyPro Plugin Integration

This plugin sends notifications to multiple channels (Discord, Telegram, Email).

**Step 1: Add Configuration**

Add to `.env`:

```env
# NotifyPro Settings
NOTIFYPRO_DISCORD_WEBHOOK=https://discord.com/api/webhooks/xxx
NOTIFYPRO_TELEGRAM_BOT_TOKEN=your_bot_token
NOTIFYPRO_TELEGRAM_CHAT_ID=your_chat_id
NOTIFYPRO_EMAIL_RECIPIENTS=admin@example.com
```

**Step 2: Create NotifyService**

Create file: `app/Services/NotifyProService.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyProService
{
    public function sendNotification(string $title, string $message, string $type = 'info'): void
    {
        $this->sendDiscord($title, $message, $type);
        $this->sendTelegram($title, $message);
    }

    protected function sendDiscord(string $title, string $message, string $type): void
    {
        $webhook = env('NOTIFYPRO_DISCORD_WEBHOOK');
        if (!$webhook) return;

        try {
            Http::post($webhook, [
                'embeds' => [[
                    'title' => $title,
                    'description' => $message,
                    'color' => $type === 'error' ? 0xFF0000 : 0x00FF00,
                ]]
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyPro Discord failed', ['error' => $e->getMessage()]);
        }
    }

    protected function sendTelegram(string $title, string $message): void
    {
        $token = env('NOTIFYPRO_TELEGRAM_BOT_TOKEN');
        $chatId = env('NOTIFYPRO_TELEGRAM_CHAT_ID');
        if (!$token || !$chatId) return;

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => "<b>{$title}</b>\n\n{$message}",
                'parse_mode' => 'HTML',
            ]);
        } catch (\Exception $e) {
            Log::error('NotifyPro Telegram failed', ['error' => $e->getMessage()]);
        }
    }
}
```

**Step 3: Add Hooks**

Add notifications in key places:

```php
// In AuthController after user registration:
app(NotifyProService::class)->sendNotification(
    '🎉 New User Registration',
    "Email: {$user->email}\nTime: " . date('Y-m-d H:i:s')
);

// In OrderService after payment:
app(NotifyProService::class)->sendNotification(
    '💰 Order Paid',
    "Order: {$order->trade_no}\nAmount: $" . ($order->total_amount / 100)
);
```

---

### UserMailer Plugin Integration

This plugin sends customer emails at key lifecycle points.

**Step 1: Add Email Events**

In `app/Providers/EventServiceProvider.php`, add:

```php
protected $listen = [
    'user.registered' => [
        \App\Listeners\SendWelcomeEmail::class,
    ],
    'order.paid' => [
        \App\Listeners\SendOrderConfirmation::class,
    ],
];
```

**Step 2: Create Listeners**

Create file: `app/Listeners/SendWelcomeEmail.php`

```php
<?php

namespace App\Listeners;

use App\Jobs\SendEmailJob;

class SendWelcomeEmail
{
    public function handle($user): void
    {
        dispatch(new SendEmailJob([
            'email' => $user->email,
            'subject' => 'Welcome to ' . config('v2board.app_name'),
            'template_name' => 'notify',
            'template_value' => [
                'name' => 'Customer',
                'content' => "Welcome! Your account has been created. Login at: " . config('v2board.app_url'),
                'url' => config('v2board.app_url'),
            ]
        ]));
    }
}
```

---

### TicketMailer Plugin Integration

This plugin sends email notifications for ticket updates.

**Step 1: Add to TicketService**

In `app/Services/TicketService.php`:

```php
public function sendTicketEmail($ticket, $isAdminReply = false): void
{
    $user = User::find($ticket->user_id);
    if (!$user) return;

    $subject = $isAdminReply 
        ? "Admin replied to your ticket #{$ticket->id}"
        : "New ticket reply #{$ticket->id}";

    dispatch(new SendEmailJob([
        'email' => $user->email,
        'subject' => "[" . config('v2board.app_name') . "] " . $subject,
        'template_name' => 'notify',
        'template_value' => [
            'name' => 'Customer',
            'content' => "Your ticket '{$ticket->subject}' has a new reply. Login to view.",
            'url' => config('v2board.app_url'),
        ]
    ]));
}
```

Call this after reply in `TicketController`:

```php
$this->ticketService->sendTicketEmail($ticket, true);
```

---

### Mugglepay Plugin Integration

This is a payment gateway plugin. To integrate:

**Step 1: Create Payment Method File**

Create file: `library/Mugglepay.php`

```php
<?php

namespace Library;

class Mugglepay
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function form()
    {
        return [
            'api_key' => [
                'label' => 'API Key',
                'description' => 'Your Mugglepay API key',
                'type' => 'input'
            ],
            'webhook_secret' => [
                'label' => 'Webhook Secret',
                'description' => 'Webhook verification secret',
                'type' => 'input'
            ]
        ];
    }

    public function pay($order)
    {
        $data = [
            'merchant_order_id' => $order['trade_no'],
            'price_amount' => $order['total_amount'] / 100,
            'price_currency' => 'CNY',
            'pay_currency' => 'USDT',
            'title' => 'Subscription - ' . config('v2board.app_name'),
            'callback_url' => $order['notify_url'],
            'success_url' => $order['return_url'],
            'cancel_url' => $order['return_url'],
        ];

        $response = $this->request('orders', $data);

        if ($response && isset($response['payment_url'])) {
            return [
                'type' => 1, // redirect
                'data' => $response['payment_url']
            ];
        }

        abort(500, 'Payment creation failed');
    }

    public function notify($params)
    {
        $signature = $_SERVER['HTTP_MUGGLEPAY_SIGNATURE'] ?? '';
        $payload = file_get_contents('php://input');
        
        $expectedSignature = hash_hmac('sha256', $payload, $this->config['webhook_secret']);
        
        if (!hash_equals($expectedSignature, $signature)) {
            abort(400, 'Invalid signature');
        }

        $data = json_decode($payload, true);
        
        if ($data['status'] === 'PAID') {
            return [
                'trade_no' => $data['merchant_order_id'],
                'callback_no' => $data['order_id']
            ];
        }

        return false;
    }

    private function request($endpoint, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mugglepay.com/v1/{$endpoint}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'token: ' . $this->config['api_key']
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}
```

**Step 2: Add to Payment Controller**

In `app/Http/Controllers/Admin/PaymentController.php`, add 'Mugglepay' to the payment methods list.

---

## Verifying Integration

After implementing any integration:

1. **Test each feature manually**
2. **Check Laravel logs**: `storage/logs/laravel.log`
3. **Verify database changes** were applied correctly
4. **Test webhook callbacks** using services like webhook.site

## Troubleshooting

### Common Issues

1. **Plugin not loading**: Check file permissions (chmod 755 for directories, 644 for files)
2. **Database errors**: Ensure migrations ran successfully
3. **Telegram not working**: Verify bot token and check webhook is set correctly
4. **Email not sending**: Check mail configuration in `.env` and queue worker is running

### Debug Mode

Enable debug mode temporarily:
```env
APP_DEBUG=true
```

Check logs at `storage/logs/laravel.log`

---

## Support

If you encounter issues:
1. Check the V2Board documentation
2. Review plugin README files in the `plugins/` directory
3. Check Laravel logs for errors
