# UserMailer - Customer Email Notifications Plugin

A comprehensive customer email plugin for XBoard v1.2.0 that sends clear, informative emails to users throughout their journey.

## Features

- **Welcome Email** - Sent on registration with special coupon code
- **Order Created** - Order details with payment link
- **Order Paid** - Payment confirmation
- **Thank You Letter** - With referral program promotion
- **Getting Started Guide** - Step-by-step setup instructions
- **Unpaid Reminders** - Automated reminders at configurable intervals

## Email Types

### 1. Welcome Email
Sent when a user registers:
- Personalized greeting
- **Limited time coupon code** (NF10 - 20% off 6+ months)
- Quick start guide
- Dashboard link

### 2. Order Created
Sent when user creates an order:
- Order number and details
- Plan name and amount
- Payment status
- Complete payment link

### 3. Order Paid
Sent when payment is successful:
- Payment confirmation
- Order summary
- Next steps guidance
- Dashboard link

### 4. Thank You Letter
Sent after payment:
- Appreciation message
- Referral program promotion
- Commission info (default 10%)
- Dashboard link for referral

### 5. Getting Started Guide
Sent after payment:
- Step 1: Login to dashboard
- Step 2: Download client app (Windows/macOS/iOS/Android)
- Step 3: Get subscription link
- Step 4: Import & connect
- Knowledge base link
- Support ticket link

### 6. Unpaid Reminders
Sent at configurable intervals (default: 1h, 6h, 24h):
- Order details
- Payment amount
- Urgency messaging
- Payment link

## Installation

1. Upload `UserMailer` folder to XBoard `/plugins/` directory
2. Go to Admin Panel → Plugins → Enable UserMailer
3. Configure settings as needed

## Configuration

### Site Settings
| Setting | Default |
|---------|---------|
| Site Name | Netflare |
| Dashboard URL | https://www.netflare.co/console |
| Knowledge URL | https://www.netflare.co/console/knowledge |
| Ticket URL | https://www.netflare.co/console/ticket |

### Welcome Email
| Setting | Default |
|---------|---------|
| Coupon Code | NF10 |
| Discount | 20% |

### Referral Program
| Setting | Default |
|---------|---------|
| Enabled | true |
| Commission | 10% |

## Troubleshooting

### Emails Not Sending
1. Check XBoard SMTP settings (Admin → Settings → Email)
2. Make sure Horizon is running
3. Check failed jobs in Horizon dashboard

### Clear Failed Queue Jobs
If you see errors about failed jobs in Laravel Horizon:
```bash
php artisan queue:flush
php artisan horizon:clear
```

## Changelog

### v1.2.0
- Fixed: Emails now use plain text format compatible with XBoard's notify template
- Fixed: Removed HTML that was not rendering correctly in XBoard's email templates

### v1.1.0
- Added: Welcome email on user registration
- Added: Configurable coupon code (NF10)
- Added: Configurable discount percentage (20%)
- Fixed: Email "From" header error - now uses XBoard's SendEmailJob queue
- Fixed: Scheduler method - now uses `everyThirtyMinutes()`

## Email Uses XBoard's SMTP

All emails are sent via XBoard's configured SMTP service using the `SendEmailJob` queue. Ensure your XBoard email settings are properly configured.

## Author

@gracelyncn | @Teamtendex
