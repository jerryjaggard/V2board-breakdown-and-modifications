# NotifyPro - Multi-Channel Admin Notifications Plugin

A comprehensive notification plugin for XBoard v1.2.0 that sends detailed alerts to Discord, Telegram, and Email.

## Features

- **Discord Webhook Notifications** - Rich embeds with detailed information
- **Telegram Notifications** - HTML formatted messages with emoji
- **Email Notifications** - Clean plain text using XBoard's email templates

### Notification Details Include

| Data | Events |
|------|--------|
| Email/User ID | All events |
| Order No/ID, Amount, Plan | Order events |
| Ticket ID, Subject, Priority | Ticket events |
| Browser, OS | New user, New order |
| IP Address | New user, New order |
| Node Name/ID, Host, Type | Node failure |

### Supported Events

| Event | Description |
|-------|-------------|
| New User Registration | User info + browser/OS/IP |
| New Order Created | Order details + user + browser/OS/IP |
| Order Paid | Order details + user info |
| Order Cancelled | Order details + user info |
| Ticket Opened | Ticket ID, subject, priority, user |
| Ticket Closed | Ticket ID, subject, user |
| Node Failure | Node name, ID, host, type |
| Daily Reports | Full statistics summary |

## Installation

1. Upload `NotifyPro` folder to XBoard `/plugins/` directory
2. Go to Admin Panel → Plugins → Enable NotifyPro
3. Configure notification channels

## Configuration

### Discord Setup
1. Create a webhook in your Discord channel
2. Paste webhook URL in plugin settings
3. Enable Discord Notifications

### Telegram Setup
1. Create a bot via [@BotFather](https://t.me/BotFather)
2. Get chat/group/channel ID
3. Enter bot token and chat ID
4. Enable Telegram Notifications

### Email Setup
- Uses XBoard's configured SMTP service (via SendEmailJob)
- Enter recipient emails (comma-separated)
- Enable Email Notifications

## Troubleshooting

### Clear Failed Queue Jobs
If you see errors about "incomplete class" in Laravel Horizon, clear the failed jobs:
```bash
php artisan queue:flush
php artisan horizon:clear
```

### Email Not Sending
- Make sure XBoard's SMTP settings are configured correctly
- Check Admin → Settings → Email

## Changelog

### v1.2.0
- Fixed: Email content now uses plain text format compatible with XBoard's notify template
- Fixed: Removed HTML from email content that was causing rendering issues

### v1.1.0
- Fixed: `everyNMinutes()` scheduler error - now uses `everyFiveMinutes()`
- Fixed: Email "From" header error - now uses XBoard's SendEmailJob queue
- Added: Browser and OS detection
- Added: IP address tracking
- Added: More detailed notification content
- Added: Plan model for order information

## Author

@gracelyncn | @Teamtendex
