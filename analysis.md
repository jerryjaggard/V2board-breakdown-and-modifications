# V2Board Complete Repository Analysis

---

## Title Block

| Field | Value |
|-------|-------|
| **Repository URL** | https://github.com/v2board/v2board |
| **Version Analyzed** | 1.7.4.1681103823832 |
| **Analysis Date** | 2025-11-27 |
| **Analyst** | GitHub Copilot |
| **Total Files** | 362 |

---

## 1. Executive Summary

V2Board is a comprehensive VPN/proxy subscription management panel built on the Laravel 8 PHP framework. It provides a complete solution for managing proxy servers (VMess, Trojan, Shadowsocks, Hysteria), user subscriptions, payment processing, and client configuration distribution. The system supports multiple proxy protocols and can generate subscription configurations for 15+ client applications including Clash, Surge, Shadowrocket, and QuantumultX.

The application follows a standard Laravel MVC architecture enhanced with a Service Layer pattern for business logic. Authentication is handled via JWT tokens using Firebase/PHP-JWT, with session data cached in Redis. The frontend is built with React/UMI framework, pre-compiled and served as static assets. The admin panel is accessible via a configurable secure path.

Runtime requirements include PHP 7.3+ or 8.0, MySQL 5.5+, Redis for caching/queues, and a web server (nginx recommended). The application uses Laravel Horizon for queue management and requires Supervisor for background job processing. Key security considerations include proper configuration of the `APP_KEY`, secure admin path setup, and HTTPS enforcement.

**Risk Summary**: The codebase has several potential security concerns including lack of rate limiting on some endpoints, potential SQL injection vectors in dynamic queries, and some hardcoded validation logic. Payment callback validation should be audited before production deployment.

---

## 2. Quick Facts Table

| Category | Details |
|----------|---------|
| **Primary Language** | PHP 7.3+/8.0 |
| **Framework** | Laravel 8.x |
| **Frontend** | React/UMI (pre-built) |
| **Database** | MySQL 5.5+ |
| **Cache/Queue** | Redis |
| **Authentication** | JWT (Firebase/PHP-JWT) |
| **Queue Manager** | Laravel Horizon |
| **Build Tools** | Composer (PHP), npm/yarn (frontend) |
| **Default Port** | 80/443 (web server) |
| **API Prefix** | `/api/v1/` |
| **License** | MIT |

---

## 3. Complete File Index

### Core Application Files

| Path | Type | LOC | Purpose | Built/Source |
|------|------|-----|---------|--------------|
| `app/Http/Controllers/Admin/*.php` | PHP | ~2000 | Admin API controllers | SOURCE |
| `app/Http/Controllers/User/*.php` | PHP | ~1200 | User API controllers | SOURCE |
| `app/Http/Controllers/Client/*.php` | PHP | ~800 | Subscription client handlers | SOURCE |
| `app/Http/Controllers/Passport/*.php` | PHP | ~400 | Authentication controllers | SOURCE |
| `app/Http/Controllers/Guest/*.php` | PHP | ~200 | Public/webhook controllers | SOURCE |
| `app/Http/Controllers/Server/*.php` | PHP | ~400 | Node backend API | SOURCE |
| `app/Http/Controllers/Staff/*.php` | PHP | ~200 | Staff panel controllers | SOURCE |
| `app/Services/*.php` | PHP | ~1500 | Business logic services | SOURCE |
| `app/Models/*.php` | PHP | ~400 | Eloquent models | SOURCE |
| `app/Jobs/*.php` | PHP | ~250 | Queue jobs | SOURCE |
| `app/Payments/*.php` | PHP | ~1200 | Payment gateway integrations | SOURCE |
| `app/Console/Commands/*.php` | PHP | ~600 | Artisan commands | SOURCE |
| `app/Http/Middleware/*.php` | PHP | ~400 | Request middleware | SOURCE |
| `app/Http/Routes/*.php` | PHP | ~350 | API route definitions | SOURCE |
| `app/Plugins/Telegram/*.php` | PHP | ~200 | Telegram bot integration | SOURCE |

### Configuration Files

| Path | Type | LOC | Purpose |
|------|------|-----|---------|
| `config/app.php` | PHP | 241 | Application configuration |
| `config/database.php` | PHP | 147 | Database connections |
| `config/horizon.php` | PHP | 190 | Queue worker configuration |
| `config/cache.php` | PHP | 103 | Cache configuration |
| `config/mail.php` | PHP | 136 | Email configuration |
| `composer.json` | JSON | 80 | PHP dependencies |
| `.env.example` | ENV | 49 | Environment template |

### Database Files

| Path | Type | LOC | Purpose |
|------|------|-----|---------|
| `database/install.sql` | SQL | 447 | Initial schema |
| `database/update.sql` | SQL | 686 | Migration updates |

### Frontend Assets (BUILT_ONLY)

| Path | Type | Purpose |
|------|------|---------|
| `public/assets/admin/umi.js` | JS | Admin panel bundle |
| `public/assets/admin/umi.css` | CSS | Admin panel styles |
| `public/theme/v2board/assets/umi.js` | JS | User panel bundle |
| `public/theme/v2board/assets/umi.css` | CSS | User panel styles |

---

## 4. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Web Browser                              │
│                    (Admin / User Panel)                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Nginx / Apache                              │
│                   (Reverse Proxy + SSL)                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        PHP-FPM                                   │
│                    (Laravel 8 App)                               │
├─────────────────────────────────────────────────────────────────┤
│  Routes (app/Http/Routes/)                                       │
│    ├── AdminRoute.php      → /api/v1/{secure_path}/*            │
│    ├── UserRoute.php       → /api/v1/user/*                     │
│    ├── PassportRoute.php   → /api/v1/passport/*                 │
│    ├── ClientRoute.php     → /api/v1/client/*                   │
│    ├── GuestRoute.php      → /api/v1/guest/*                    │
│    ├── ServerRoute.php     → /api/v1/server/*                   │
│    └── StaffRoute.php      → /api/v1/staff/*                    │
├─────────────────────────────────────────────────────────────────┤
│  Middleware (app/Http/Middleware/)                               │
│    ├── Admin.php           → Admin authentication               │
│    ├── User.php            → User authentication                │
│    ├── Client.php          → Subscription token auth            │
│    ├── Staff.php           → Staff authentication               │
│    └── ForceJson.php       → API response format                │
├─────────────────────────────────────────────────────────────────┤
│  Controllers (app/Http/Controllers/)                             │
│    └── Delegates to Services                                     │
├─────────────────────────────────────────────────────────────────┤
│  Services (app/Services/)                                        │
│    ├── AuthService         → JWT token management               │
│    ├── OrderService        → Order processing                   │
│    ├── PaymentService      → Payment gateway routing            │
│    ├── ServerService       → Node management                    │
│    ├── UserService         → User operations                    │
│    └── ...                                                       │
├─────────────────────────────────────────────────────────────────┤
│  Models (app/Models/)                                            │
│    └── Eloquent ORM entities                                     │
└─────────────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
┌─────────────┐      ┌─────────────┐      ┌─────────────┐
│   MySQL     │      │    Redis    │      │  Horizon    │
│  Database   │      │   Cache     │      │   Queue     │
└─────────────┘      └─────────────┘      └─────────────┘
                                                  │
                                                  ▼
                                          ┌─────────────┐
                                          │  External   │
                                          │  Services   │
                                          │ - Telegram  │
                                          │ - Payment   │
                                          │   Gateways  │
                                          │ - SMTP      │
                                          └─────────────┘
```

### Layer Mapping

| Layer | Files/Folders |
|-------|---------------|
| **Presentation** | `public/`, `resources/views/` |
| **Routing** | `app/Http/Routes/`, `routes/web.php` |
| **Controllers** | `app/Http/Controllers/` |
| **Middleware** | `app/Http/Middleware/` |
| **Services** | `app/Services/` |
| **Models** | `app/Models/` |
| **Jobs** | `app/Jobs/` |
| **Database** | `database/` |
| **Configuration** | `config/` |

---

## 5. Request Lifecycle

### Authentication Flow (Login)

```
1. POST /api/v1/passport/auth/login
   ├── Request: { email, password }
   │
2. PassportRoute.php → AuthController@login
   │
3. AuthController.php (line 188-230)
   ├── Validate credentials
   ├── Check password limit (rate limiting)
   ├── Verify password with Helper::multiPasswordVerify()
   ├── Check if user is banned
   │
4. AuthService.php (line 22-38)
   ├── Generate JWT token with user ID + session GUID
   ├── Store session in Redis cache
   │
5. Response: { token, is_admin, auth_data }
```

### Order/Payment Flow

```
1. POST /api/v1/user/order/save
   ├── Request: { plan_id, period, coupon_code? }
   │
2. UserRoute.php → OrderController@save
   │
3. OrderController.php (line 72-168)
   ├── Check for incomplete orders
   ├── Validate plan availability
   ├── Apply coupon if provided
   ├── Calculate discounts and surplus
   ├── Create Order record
   │
4. POST /api/v1/user/order/checkout
   ├── Request: { trade_no, method }
   │
5. OrderController.php (line 171-209)
   ├── Load Payment gateway
   ├── PaymentService->pay() generates payment URL
   │
6. User redirected to payment gateway
   │
7. POST /api/v1/guest/payment/notify/{method}/{uuid}
   │
8. Guest/PaymentController.php (line 14-48)
   ├── PaymentService->notify() validates callback
   ├── OrderService->paid() marks order complete
   ├── OrderHandleJob dispatched
   │
9. OrderHandleJob.php
   ├── OrderService->open() activates subscription
```

### Node Reporting Flow

```
1. GET /api/v1/server/uniProxy/user?token=xxx&node_type=xxx&node_id=xxx
   │
2. ServerRoute.php → UniProxyController@user
   │
3. UniProxyController.php (line 42-56)
   ├── Validate server token
   ├── Update last check timestamp in cache
   ├── Return available users for node
   │
4. POST /api/v1/server/uniProxy/push
   ├── Request: { user_id: [upload, download], ... }
   │
5. UniProxyController.php (line 59-71)
   ├── Update online user count cache
   ├── Dispatch TrafficFetchJob for each user
```

---

## 6. Full API Catalog

### Passport Routes (Public)

| Method | Path | Controller | Auth | Description |
|--------|------|------------|------|-------------|
| POST | `/passport/auth/register` | AuthController@register | None | User registration |
| POST | `/passport/auth/login` | AuthController@login | None | User login |
| GET | `/passport/auth/token2Login` | AuthController@token2Login | None | Token-based login |
| POST | `/passport/auth/forget` | AuthController@forget | None | Password reset |
| POST | `/passport/auth/getQuickLoginUrl` | AuthController@getQuickLoginUrl | None | Quick login URL |
| POST | `/passport/auth/loginWithMailLink` | AuthController@loginWithMailLink | None | Email magic link |
| POST | `/passport/comm/sendEmailVerify` | CommController@sendEmailVerify | None | Send verification |
| POST | `/passport/comm/pv` | CommController@pv | None | Page view tracking |

### User Routes (Authenticated)

| Method | Path | Controller | Description |
|--------|------|------------|-------------|
| GET | `/user/info` | UserController@info | Get user info |
| POST | `/user/changePassword` | UserController@changePassword | Change password |
| POST | `/user/update` | UserController@update | Update profile |
| GET | `/user/getSubscribe` | UserController@getSubscribe | Get subscription URL |
| GET | `/user/getStat` | UserController@getStat | Get usage statistics |
| POST | `/user/transfer` | UserController@transfer | Transfer balance |
| POST | `/user/order/save` | OrderController@save | Create order |
| POST | `/user/order/checkout` | OrderController@checkout | Checkout order |
| GET | `/user/order/fetch` | OrderController@fetch | List orders |
| GET | `/user/order/detail` | OrderController@detail | Order details |
| POST | `/user/order/cancel` | OrderController@cancel | Cancel order |
| GET | `/user/plan/fetch` | PlanController@fetch | List plans |
| GET | `/user/server/fetch` | ServerController@fetch | List servers |
| POST | `/user/ticket/save` | TicketController@save | Create ticket |
| GET | `/user/ticket/fetch` | TicketController@fetch | List tickets |
| POST | `/user/ticket/reply` | TicketController@reply | Reply to ticket |
| POST | `/user/coupon/check` | CouponController@check | Validate coupon |
| GET | `/user/notice/fetch` | NoticeController@fetch | List notices |
| GET | `/user/knowledge/fetch` | KnowledgeController@fetch | List knowledge base |
| GET | `/user/invite/fetch` | InviteController@fetch | Get invite codes |
| GET | `/user/invite/details` | InviteController@details | Invite statistics |

### Admin Routes (Admin Auth + Secure Path)

| Method | Path | Controller | Description |
|--------|------|------------|-------------|
| GET | `/config/fetch` | ConfigController@fetch | Get all config |
| POST | `/config/save` | ConfigController@save | Save config |
| GET | `/plan/fetch` | PlanController@fetch | List plans |
| POST | `/plan/save` | PlanController@save | Create plan |
| POST | `/plan/update` | PlanController@update | Update plan |
| POST | `/plan/drop` | PlanController@drop | Delete plan |
| GET | `/user/fetch` | UserController@fetch | List users |
| POST | `/user/update` | UserController@update | Update user |
| POST | `/user/generate` | UserController@generate | Generate users |
| POST | `/user/ban` | UserController@ban | Ban users |
| GET | `/order/fetch` | OrderController@fetch | List orders |
| POST | `/order/update` | OrderController@update | Update order |
| POST | `/order/paid` | OrderController@paid | Mark paid |
| GET | `/server/group/fetch` | GroupController@fetch | List groups |
| POST | `/server/group/save` | GroupController@save | Create group |
| GET | `/server/vmess/fetch` | VmessController@fetch | List VMess |
| POST | `/server/vmess/save` | VmessController@save | Create VMess |
| GET | `/server/trojan/fetch` | TrojanController@fetch | List Trojan |
| POST | `/server/trojan/save` | TrojanController@save | Create Trojan |
| GET | `/server/shadowsocks/fetch` | ShadowsocksController@fetch | List SS |
| POST | `/server/shadowsocks/save` | ShadowsocksController@save | Create SS |
| GET | `/server/hysteria/fetch` | HysteriaController@fetch | List Hysteria |
| POST | `/server/hysteria/save` | HysteriaController@save | Create Hysteria |
| GET | `/payment/fetch` | PaymentController@fetch | List payments |
| POST | `/payment/save` | PaymentController@save | Create payment |
| GET | `/coupon/fetch` | CouponController@fetch | List coupons |
| POST | `/coupon/generate` | CouponController@generate | Create coupon |
| GET | `/ticket/fetch` | TicketController@fetch | List tickets |
| POST | `/ticket/reply` | TicketController@reply | Reply ticket |
| GET | `/stat/getStat` | StatController@getStat | Statistics |
| GET | `/system/getSystemStatus` | SystemController@getSystemStatus | System status |

### Client Routes (Token Auth)

| Method | Path | Controller | Description |
|--------|------|------------|-------------|
| GET | `/client/subscribe` | ClientController@subscribe | Get subscription |
| GET | `/client/app/getConfig` | AppController@getConfig | App config |
| GET | `/client/app/getVersion` | AppController@getVersion | App version |

### Server Routes (Server Token Auth)

| Method | Path | Controller | Description |
|--------|------|------------|-------------|
| GET | `/server/uniProxy/user` | UniProxyController@user | Get users |
| POST | `/server/uniProxy/push` | UniProxyController@push | Push traffic |
| GET | `/server/uniProxy/config` | UniProxyController@config | Get config |

### Guest Routes (Public)

| Method | Path | Controller | Description |
|--------|------|------------|-------------|
| GET | `/guest/plan/fetch` | PlanController@fetch | Public plans |
| GET/POST | `/guest/payment/notify/{method}/{uuid}` | PaymentController@notify | Payment callback |
| POST | `/guest/telegram/webhook` | TelegramController@webhook | Telegram webhook |
| GET | `/guest/comm/config` | CommController@config | Public config |

---

## 7. Hooks & Extension Points

### Service Providers

| File | Purpose | Hook Into |
|------|---------|-----------|
| `app/Providers/AppServiceProvider.php` | App bootstrapping | `boot()` method |
| `app/Providers/RouteServiceProvider.php` | Route registration | `mapApiRoutes()` |
| `app/Providers/EventServiceProvider.php` | Event listeners | `$listen` array |
| `app/Providers/HorizonServiceProvider.php` | Queue dashboard | `gate()` method |

### Middleware Hooks

| Middleware | Trigger Point | Extension |
|------------|---------------|-----------|
| `Admin.php` | Admin routes | Add admin checks |
| `User.php` | User routes | Add user validation |
| `Client.php` | Subscription access | Modify token validation |
| `RequestLog.php` | Admin requests | Add logging |

### Payment Gateway Extension

```php
// Location: app/Payments/
// Pattern: Create new file CustomGateway.php

namespace App\Payments;

class CustomGateway {
    public function __construct($config) {
        $this->config = $config;
    }

    public function form() {
        return [
            'api_key' => [
                'label' => 'API Key',
                'type' => 'input',
            ],
        ];
    }

    public function pay($order) {
        // Return: ['type' => 0|1, 'data' => 'url_or_qrcode']
        // type 0 = QR code, type 1 = redirect URL
    }

    public function notify($params) {
        // Validate callback
        // Return: ['trade_no' => '', 'callback_no' => '']
    }
}
```

### Client Protocol Extension

```php
// Location: app/Http/Controllers/Client/Protocols/
// Pattern: Create new file CustomClient.php

namespace App\Http\Controllers\Client\Protocols;

class CustomClient {
    public $flag = 'customclient'; // User-Agent match
    private $servers;
    private $user;

    public function __construct($user, $servers) {
        $this->user = $user;
        $this->servers = $servers;
    }

    public function handle() {
        // Build and return subscription config
        return $configString;
    }
}
```

### Scheduled Tasks

| Command | Schedule | File |
|---------|----------|------|
| `v2board:statistics` | Daily 0:10 | `V2boardStatistics.php` |
| `check:order` | Every minute | `CheckOrder.php` |
| `check:commission` | Every minute | `CheckCommission.php` |
| `check:ticket` | Every minute | `CheckTicket.php` |
| `reset:traffic` | Daily | `ResetTraffic.php` |
| `reset:log` | Daily | `ResetLog.php` |
| `send:remindMail` | Daily 11:30 | `SendRemindMail.php` |
| `horizon:snapshot` | Every 5 min | Horizon metrics |

### Queue Workers

| Queue | Job | Purpose |
|-------|-----|---------|
| `order_handle` | OrderHandleJob | Process paid orders |
| `traffic_fetch` | TrafficFetchJob | Update user traffic |
| `send_email` | SendEmailJob | Send emails |
| `send_telegram` | SendTelegramJob | Send Telegram messages |

---

## 8. Data Model & DB Schema

### Entity Relationship Diagram (ERD)

```
┌─────────────────┐      ┌─────────────────┐      ┌─────────────────┐
│    v2_user      │──────│    v2_order     │──────│    v2_plan      │
├─────────────────┤      ├─────────────────┤      ├─────────────────┤
│ id (PK)         │      │ id (PK)         │      │ id (PK)         │
│ email (UNIQUE)  │      │ user_id (FK)    │      │ group_id        │
│ password        │      │ plan_id (FK)    │      │ name            │
│ uuid            │      │ trade_no (UNIQUE)      │ transfer_enable │
│ token           │      │ total_amount    │      │ month_price     │
│ plan_id (FK)    │      │ status          │      │ year_price      │
│ group_id (FK)   │      │ paid_at         │      │ ...             │
│ transfer_enable │      └─────────────────┘      └─────────────────┘
│ u (upload)      │                │
│ d (download)    │                │
│ expired_at      │      ┌─────────────────┐
│ is_admin        │      │   v2_payment    │
│ banned          │      ├─────────────────┤
└─────────────────┘      │ id (PK)         │
        │                │ uuid            │
        │                │ payment         │
        │                │ config (JSON)   │
┌───────┴───────┐        │ enable          │
│               │        └─────────────────┘
▼               ▼
┌─────────────────┐      ┌─────────────────┐
│ v2_invite_code  │      │   v2_ticket     │
├─────────────────┤      ├─────────────────┤
│ id (PK)         │      │ id (PK)         │
│ user_id (FK)    │      │ user_id (FK)    │
│ code            │      │ subject         │
│ status          │      │ level           │
│ pv              │      │ status          │
└─────────────────┘      └─────────────────┘
```

### Table Definitions

#### v2_user
```sql
CREATE TABLE v2_user (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invite_user_id INT,
    telegram_id BIGINT,
    email VARCHAR(64) UNIQUE NOT NULL,
    password VARCHAR(64) NOT NULL,
    password_algo CHAR(10),
    password_salt CHAR(10),
    balance INT DEFAULT 0,
    discount INT,
    commission_type TINYINT DEFAULT 0,
    commission_rate INT,
    commission_balance INT DEFAULT 0,
    t INT DEFAULT 0,
    u BIGINT DEFAULT 0,
    d BIGINT DEFAULT 0,
    transfer_enable BIGINT DEFAULT 0,
    banned TINYINT DEFAULT 0,
    is_admin TINYINT DEFAULT 0,
    is_staff TINYINT DEFAULT 0,
    last_login_at INT,
    last_login_ip INT,
    uuid VARCHAR(36) NOT NULL,
    group_id INT,
    plan_id INT,
    speed_limit INT,
    remind_expire TINYINT DEFAULT 1,
    remind_traffic TINYINT DEFAULT 1,
    token CHAR(32) NOT NULL,
    expired_at BIGINT DEFAULT 0,
    remarks TEXT,
    created_at INT NOT NULL,
    updated_at INT NOT NULL
);
```

#### v2_order
```sql
CREATE TABLE v2_order (
    id INT PRIMARY KEY AUTO_INCREMENT,
    invite_user_id INT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    coupon_id INT,
    payment_id INT,
    type INT NOT NULL COMMENT '1=new,2=renew,3=upgrade',
    period VARCHAR(255) NOT NULL,
    trade_no VARCHAR(36) UNIQUE NOT NULL,
    callback_no VARCHAR(255),
    total_amount INT NOT NULL,
    handling_amount INT,
    discount_amount INT,
    surplus_amount INT,
    refund_amount INT,
    balance_amount INT,
    surplus_order_ids TEXT,
    status TINYINT DEFAULT 0 COMMENT '0=pending,1=processing,2=cancelled,3=completed,4=offset',
    commission_status TINYINT DEFAULT 0,
    commission_balance INT DEFAULT 0,
    actual_commission_balance INT,
    paid_at INT,
    created_at INT NOT NULL,
    updated_at INT NOT NULL
);
```

#### v2_plan
```sql
CREATE TABLE v2_plan (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    transfer_enable INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    speed_limit INT,
    show TINYINT DEFAULT 0,
    sort INT,
    renew TINYINT DEFAULT 1,
    content TEXT,
    month_price INT,
    quarter_price INT,
    half_year_price INT,
    year_price INT,
    two_year_price INT,
    three_year_price INT,
    onetime_price INT,
    reset_price INT,
    reset_traffic_method TINYINT,
    capacity_limit INT,
    created_at INT NOT NULL,
    updated_at INT NOT NULL
);
```

#### Server Tables (v2_server_vmess, v2_server_trojan, v2_server_shadowsocks, v2_server_hysteria)

Common structure:
```sql
CREATE TABLE v2_server_[type] (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id VARCHAR(255) NOT NULL,
    route_id VARCHAR(255),
    parent_id INT,
    tags VARCHAR(255),
    name VARCHAR(255) NOT NULL,
    rate VARCHAR(11) NOT NULL,
    host VARCHAR(255) NOT NULL,
    port VARCHAR(11) NOT NULL,
    server_port INT NOT NULL,
    show TINYINT DEFAULT 0,
    sort INT,
    -- Protocol-specific fields --
    created_at INT NOT NULL,
    updated_at INT NOT NULL
);
```

---

## 9. Payments & Gateways

### Implemented Gateways

| Gateway | File | Currencies | Callback Validation |
|---------|------|------------|---------------------|
| AlipayF2F | `AlipayF2F.php` | CNY | RSA signature |
| BTCPay | `BTCPay.php` | BTC | HMAC signature |
| Coinbase | `Coinbase.php` | Multi-crypto | Webhook signature |
| CoinPayments | `CoinPayments.php` | Multi-crypto | HMAC-SHA512 |
| EPay | `EPay.php` | CNY | MD5 signature |
| MGate | `MGate.php` | CNY | MD5 signature |
| StripeAlipay | `StripeAlipay.php` | Multi | Stripe webhook |
| StripeCheckout | `StripeCheckout.php` | Multi | Stripe webhook |
| StripeCredit | `StripeCredit.php` | Multi | Stripe webhook |
| StripeWepay | `StripeWepay.php` | CNY | Stripe webhook |
| WechatPayNative | `WechatPayNative.php` | CNY | WeChat signature |

### Payment Flow

```php
// 1. PaymentService instantiation
$paymentService = new PaymentService($method, $paymentId);

// 2. Generate payment request
$result = $paymentService->pay([
    'notify_url' => $notifyUrl,
    'return_url' => $returnUrl,
    'trade_no' => $order['trade_no'],
    'total_amount' => $order['total_amount'],
    'user_id' => $order['user_id']
]);

// 3. Callback handling in Guest/PaymentController
$verify = $paymentService->notify($request->input());
if ($verify) {
    $orderService->paid($verify['trade_no'], $verify['callback_no']);
}
```

### Safe Extension Pattern

```php
// Location: app/Payments/NewGateway.php
namespace App\Payments;

class NewGateway {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function form() {
        return [
            'api_key' => ['label' => 'API Key', 'type' => 'input'],
            'api_secret' => ['label' => 'API Secret', 'type' => 'input'],
        ];
    }
    
    public function pay($order) {
        // Implement payment initiation
        // Must return: ['type' => 0|1, 'data' => $url_or_qrcode]
    }
    
    public function notify($params) {
        // Validate signature
        // Must return: ['trade_no' => $tradeNo, 'callback_no' => $callbackNo]
        // Or return false on validation failure
    }
}
```

---

## 10. Node / Server Logic

### Node Types

| Type | Model | Controller | Protocol |
|------|-------|------------|----------|
| VMess | `ServerVmess.php` | `VmessController.php` | V2Ray VMess |
| Trojan | `ServerTrojan.php` | `TrojanController.php` | Trojan |
| Shadowsocks | `ServerShadowsocks.php` | `ShadowsocksController.php` | Shadowsocks |
| Hysteria | `ServerHysteria.php` | `HysteriaController.php` | Hysteria |

### Node Enrollment

1. Admin creates server via Admin panel
2. Server is assigned to group(s) via `group_id` (JSON array)
3. Plans are assigned to groups
4. Users with matching plan get access to servers in those groups

### Heartbeat/Stat Reporting

```php
// UniProxyController handles all node types

// 1. Node checks in for user list
GET /api/v1/server/uniProxy/user
    ?token={server_token}
    &node_type=vmess|trojan|shadowsocks|hysteria
    &node_id={server_id}

// Response includes available users with UUID and speed limits
{
    "users": [
        {"id": 1, "uuid": "xxx", "speed_limit": 0},
        ...
    ]
}

// 2. Node pushes traffic stats
POST /api/v1/server/uniProxy/push
{
    "user_id": [upload_bytes, download_bytes],
    ...
}

// 3. Node gets configuration
GET /api/v1/server/uniProxy/config
// Returns protocol-specific configuration
```

### Traffic Collection

```php
// UserService::trafficFetch()
foreach ($data as $userId => [$u, $d]) {
    TrafficFetchJob::dispatch($u, $d, $userId, $server, $protocol);
}

// TrafficFetchJob updates user record
$user->u += $u * $server['rate'];  // Apply rate multiplier
$user->d += $d * $server['rate'];
$user->t = time();  // Last active timestamp
$user->save();
```

### Server Selection Logic

```php
// ServerService::getAvailableServers()
1. Get all servers of each type
2. Filter by show = 1
3. Filter by user's group_id matching server's group_id array
4. Apply random port if range specified
5. Check online status (last_check_at within 5 minutes)
6. Sort by sort field
7. Return merged array
```

---

## 11. Frontend Assets & Build

### Frontend Stack

| Component | Technology |
|-----------|------------|
| Framework | React (UMI) |
| Build Tool | UMI/Webpack |
| UI Library | Ant Design |
| State | DVA/Redux |
| i18n | UMI i18n |

### Asset Structure

```
public/
├── assets/
│   └── admin/
│       ├── umi.js              # Admin bundle (minified)
│       ├── umi.css             # Admin styles
│       ├── components.async.js  # Lazy-loaded components
│       ├── vendors.async.js     # Vendor bundle
│       ├── static/              # Fonts/icons
│       └── theme/               # Color themes
└── theme/
    └── v2board/
        ├── assets/
        │   ├── umi.js          # User bundle
        │   ├── umi.css         # User styles
        │   ├── i18n/           # Translations
        │   └── images/         # Client icons
        ├── config.json         # Theme config
        └── dashboard.blade.php # Entry template
```

### Build Pipeline

The frontend is pre-built. To modify:

1. Obtain frontend source repository (not included)
2. Install dependencies: `npm install` or `yarn`
3. Build: `npm run build` or `yarn build`
4. Copy built assets to `public/` directory

### Theme Configuration

```json
// public/theme/v2board/config.json
{
    "name": "V2Board",
    "version": "1.5.5",
    "description": "V2Board Default Theme",
    "theme_color": "#409EFF",
    "theme_sidebar": "dark",
    "theme_header": "light",
    "background_url": "",
    "custom_html": ""
}
```

---

## 12. Plugin/Theme Integration Points

### Theme Registration

```php
// ThemeService.php
public function init() {
    $path = public_path('theme/' . $this->theme . '/config.json');
    $config = json_decode(file_get_contents($path), true);
    Config::set("theme.{$this->theme}", $config);
}
```

### Custom Theme Structure

```
public/theme/custom_theme/
├── assets/
│   ├── umi.js           # Required: Main bundle
│   ├── umi.css          # Required: Styles
│   └── ...
├── config.json          # Required: Theme config
└── dashboard.blade.php  # Required: Entry template
```

### Plugin Folder Layout (Recommended)

```
app/Plugins/
├── CustomPlugin/
│   ├── Plugin.php           # Plugin bootstrap
│   ├── Routes/
│   │   └── api.php          # Custom routes
│   ├── Controllers/
│   │   └── CustomController.php
│   ├── Services/
│   │   └── CustomService.php
│   ├── Models/
│   │   └── CustomModel.php
│   ├── Migrations/
│   │   └── create_custom_table.php
│   └── config.php           # Plugin config
```

### Safe Plugin Install Steps

1. Create plugin directory structure
2. Register routes in `Plugin.php` boot method
3. Run migrations if database changes needed
4. Add configuration to admin panel
5. Clear caches: `php artisan config:clear && php artisan cache:clear`

---

## 13. Config & Environment

### Critical .env Keys

| Key | Default | Description |
|-----|---------|-------------|
| `APP_KEY` | (generated) | Encryption key - NEVER share |
| `APP_DEBUG` | false | Debug mode - false in production |
| `APP_URL` | http://localhost | Application URL |
| `DB_*` | varies | Database connection |
| `REDIS_*` | 127.0.0.1:6379 | Redis connection |
| `QUEUE_CONNECTION` | redis | Queue driver |
| `MAIL_*` | varies | Email configuration |

### V2Board Config (in database)

Stored in database via ConfigController, accessed via `config('v2board.key')`:

| Key | Description |
|-----|-------------|
| `app_name` | Application name |
| `app_url` | Application URL |
| `secure_path` | Admin panel path |
| `server_token` | Node communication token |
| `telegram_bot_token` | Telegram bot token |
| `email_verify` | Enable email verification |
| `invite_force` | Require invite code |
| `stop_register` | Disable registration |
| `try_out_plan_id` | Free trial plan |
| `subscribe_url` | Subscription URL override |

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    root /var/www/v2board/public;
    index index.php;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        try_files $uri $uri/ /index.php$is_args$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Supervisor Configuration

```ini
[program:v2board-horizon]
process_name=%(program_name)s
command=php /var/www/v2board/artisan horizon
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/v2board/storage/logs/horizon.log

[program:v2board-schedule]
process_name=%(program_name)s
command=/bin/bash -c "while [ true ]; do php /var/www/v2board/artisan schedule:run; sleep 60; done"
autostart=true
autorestart=true
user=www-data
```

---

## 14. Security Audit

### Potential Vulnerabilities

#### SQL Injection Concerns

| File | Line | Issue | Severity | Fix |
|------|------|-------|----------|-----|
| `Admin/UserController.php` | 36-52 | Dynamic filter conditions | MEDIUM | Use parameter binding |
| `database/update.sql` | 499-520 | Stored procedure | LOW | Review procedure |

#### XSS Concerns

| File | Issue | Severity |
|------|-------|----------|
| `resources/views/mail/*.blade.php` | User content in emails | LOW |
| Knowledge body content | HTML stored directly | MEDIUM |

#### Authentication Issues

| Issue | Location | Severity | Recommendation |
|-------|----------|----------|----------------|
| No rate limit on login | `AuthController.php` | MEDIUM | Implement throttling |
| Password attempts in cache | `AuthController.php` | LOW | Consider Redis persistence |
| JWT in query string | Multiple | LOW | Use headers only |

#### Input Validation

```php
// Recommended: Add validation in Form Requests
// Location: app/Http/Requests/

// Example fix for user update
public function rules() {
    return [
        'email' => 'required|email|max:64',
        'password' => 'nullable|min:8|max:64',
        // Add explicit type and length constraints
    ];
}
```

### Security Recommendations

1. **Enable HTTPS Only**: Set `force_https` in config
2. **Rotate APP_KEY**: After any suspected compromise
3. **Limit Admin Path**: Use long, random `secure_path`
4. **Review Payment Callbacks**: Validate all signatures
5. **Add Rate Limiting**: Implement `throttle` middleware
6. **Sanitize HTML**: Use `htmlspecialchars()` for user content
7. **Update Dependencies**: Run `composer update` regularly

---

## 15. Performance & Scalability

### Heavy Queries

| Location | Query | Issue | Fix |
|----------|-------|-------|-----|
| `ServerService.php` | Get all servers | N+1 potential | Eager load relations |
| `UserController.php` | User list with filters | Dynamic ordering | Add indexes |
| `StatisticalService.php` | Traffic aggregation | Large table scans | Partitioning |

### Missing Indexes

```sql
-- Recommended additions
ALTER TABLE v2_order ADD INDEX idx_user_status (user_id, status);
ALTER TABLE v2_order ADD INDEX idx_created_at (created_at);
ALTER TABLE v2_stat_user ADD INDEX idx_user_record (user_id, record_at);
ALTER TABLE v2_server_log ADD INDEX idx_user_log (user_id, log_at);
```

### Caching Suggestions

```php
// Already implemented
- Server online status (5 min cache)
- User session data (1 hour cache)
- JWT validation cache

// Recommended additions
- Plan list (cache for 10 min)
- Server list per group (cache for 5 min)
- Statistics aggregation (cache for 1 hour)
```

### Queue Optimization

```php
// Current queues
'order_handle' - Process orders
'traffic_fetch' - Update traffic
'send_email' - Send emails
'send_email_mass' - Bulk emails
'send_telegram' - Telegram messages

// Recommendation: Add more workers for traffic_fetch
// in config/horizon.php
'traffic-workers' => [
    'connection' => 'redis',
    'queue' => ['traffic_fetch'],
    'balance' => 'auto',
    'processes' => 10,
    'tries' => 3,
],
```

---

## 16. Testing & QA

### Existing Test Coverage

| Type | Location | Files |
|------|----------|-------|
| Unit | `tests/Unit/` | ExampleTest.php |
| Feature | `tests/Feature/` | ExampleTest.php |
| Bootstrap | `tests/` | Bootstrap.php, TestCase.php |

### Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=ExampleTest
```

### Recommended Tests

```php
// tests/Feature/AuthTest.php
public function test_user_can_register()
public function test_user_can_login()
public function test_invalid_login_fails()
public function test_password_reset_works()

// tests/Feature/OrderTest.php
public function test_order_creation()
public function test_order_payment_flow()
public function test_order_cancellation()
public function test_coupon_application()

// tests/Feature/SubscriptionTest.php
public function test_subscription_generation()
public function test_client_protocol_output()
public function test_traffic_usage_update()

// tests/Unit/ServiceTest.php
public function test_order_service_calculations()
public function test_coupon_discount_logic()
public function test_traffic_reset_timing()
```

---

## 17. Change-Plan & Plugin Ideas

### Priority Plugin Ideas

| # | Plugin | Value | Invasiveness | Complexity | Files Affected |
|---|--------|-------|--------------|------------|----------------|
| 1 | **Multi-Currency Support** | HIGH | MEDIUM | MEDIUM | OrderService, Payments |
| 2 | **Affiliate Dashboard** | HIGH | LOW | LOW | New controller, views |
| 3 | **Usage Analytics** | HIGH | LOW | MEDIUM | New service, cron |
| 4 | **Auto-Renewal** | HIGH | MEDIUM | MEDIUM | OrderService, cron |
| 5 | **Node Health Monitor** | MEDIUM | LOW | LOW | New service, cron |
| 6 | **Custom Pricing Rules** | MEDIUM | MEDIUM | HIGH | PlanService, OrderService |
| 7 | **Reseller System** | HIGH | HIGH | HIGH | Multiple new tables |
| 8 | **API Rate Limiting** | MEDIUM | LOW | LOW | Middleware |
| 9 | **Two-Factor Auth** | HIGH | MEDIUM | MEDIUM | AuthService |
| 10 | **Notification Center** | MEDIUM | LOW | MEDIUM | New service |
| 11 | **Traffic Prediction** | LOW | LOW | HIGH | New service, ML |
| 12 | **White-Label Theming** | MEDIUM | LOW | LOW | ThemeService |

---

## 18. Full Plugin Specification: Multi-Currency Support

### Overview

Add support for multiple currencies with automatic exchange rate conversion.

### File Layout

```
app/Plugins/MultiCurrency/
├── Plugin.php
├── config.php
├── Routes/
│   └── api.php
├── Controllers/
│   └── CurrencyController.php
├── Services/
│   └── CurrencyService.php
├── Models/
│   └── Currency.php
├── Migrations/
│   └── 2024_01_01_create_currencies_table.php
├── Jobs/
│   └── UpdateExchangeRates.php
└── Tests/
    └── CurrencyTest.php
```

### Database Migration

```php
Schema::create('v2_currency', function (Blueprint $table) {
    $table->id();
    $table->string('code', 3)->unique();
    $table->string('name');
    $table->string('symbol', 10);
    $table->decimal('exchange_rate', 10, 6);
    $table->boolean('enabled')->default(true);
    $table->timestamps();
});

// Add to orders
Schema::table('v2_order', function (Blueprint $table) {
    $table->string('currency', 3)->default('CNY');
    $table->decimal('exchange_rate', 10, 6)->default(1);
});
```

### Routes

```php
// Admin routes
Route::get('/currency/fetch', [CurrencyController::class, 'fetch']);
Route::post('/currency/save', [CurrencyController::class, 'save']);
Route::post('/currency/update-rates', [CurrencyController::class, 'updateRates']);
```

### Service Implementation

```php
namespace App\Plugins\MultiCurrency\Services;

class CurrencyService
{
    public function convert($amount, $from, $to)
    {
        $fromRate = Currency::where('code', $from)->value('exchange_rate');
        $toRate = Currency::where('code', $to)->value('exchange_rate');
        return ($amount / $fromRate) * $toRate;
    }
    
    public function updateRates()
    {
        $response = Http::get('https://api.exchangerate.host/latest?base=CNY');
        foreach ($response['rates'] as $code => $rate) {
            Currency::where('code', $code)->update(['exchange_rate' => $rate]);
        }
    }
    
    public function formatPrice($amount, $currency)
    {
        $curr = Currency::where('code', $currency)->first();
        return $curr->symbol . number_format($amount / 100, 2);
    }
}
```

### Integration Points

```php
// Modify OrderController@save
$currency = $request->input('currency', 'CNY');
$order->currency = $currency;
$order->exchange_rate = $currencyService->getRate($currency);
$order->total_amount = $currencyService->convert(
    $plan->price,
    'CNY',
    $currency
);

// Modify payment gateway calls
$paymentAmount = $order->total_amount; // Already converted
```

### Admin UI Wireframe

```
┌─────────────────────────────────────────────────────────────┐
│ Currency Management                           [Update Rates]│
├─────────────────────────────────────────────────────────────┤
│ ┌─────────────────────────────────────────────────────────┐ │
│ │ Code │ Name        │ Symbol │ Rate   │ Enabled │ Action │ │
│ ├──────┼─────────────┼────────┼────────┼─────────┼────────┤ │
│ │ CNY  │ Chinese Yuan│ ¥      │ 1.0000 │   ✓     │ [Edit] │ │
│ │ USD  │ US Dollar   │ $      │ 0.1400 │   ✓     │ [Edit] │ │
│ │ EUR  │ Euro        │ €      │ 0.1280 │   ✓     │ [Edit] │ │
│ └─────────────────────────────────────────────────────────┘ │
│                                              [Add Currency] │
└─────────────────────────────────────────────────────────────┘
```

### Scheduled Job

```php
// Add to Console/Kernel.php
$schedule->job(new UpdateExchangeRates)->dailyAt('00:00');
```

### Tests

```php
public function test_currency_conversion()
{
    $service = new CurrencyService();
    $result = $service->convert(10000, 'CNY', 'USD');
    $this->assertIsFloat($result);
}

public function test_order_with_currency()
{
    $response = $this->postJson('/api/v1/user/order/save', [
        'plan_id' => 1,
        'period' => 'month_price',
        'currency' => 'USD'
    ]);
    $response->assertStatus(200);
}
```

---

## 19. Step-by-Step Install & Dev Setup

### Requirements

- PHP 7.3+ or 8.0+
- Composer 2.x
- MySQL 5.5+ or MariaDB 10.2+
- Redis 5+
- Nginx or Apache
- Supervisor (for queues)
- Node.js 14+ (for frontend development)

### Installation Commands

```bash
# 1. Clone repository
git clone https://github.com/v2board/v2board.git /var/www/v2board
cd /var/www/v2board

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure database in .env
nano .env
# Set DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 6. Install V2Board
php artisan v2board:install
# Follow prompts for admin email

# 7. Set permissions
chown -R www-data:www-data /var/www/v2board
chmod -R 755 /var/www/v2board
chmod -R 775 storage bootstrap/cache

# 8. Configure web server (nginx example)
nano /etc/nginx/sites-available/v2board
# See nginx config in Section 13

# 9. Enable site
ln -s /etc/nginx/sites-available/v2board /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# 10. Setup Supervisor
nano /etc/supervisor/conf.d/v2board.conf
# See supervisor config in Section 13
supervisorctl reread
supervisorctl update
supervisorctl start all

# 11. Setup cron
crontab -e
# Add: * * * * * php /var/www/v2board/artisan schedule:run >> /dev/null 2>&1

# 12. Access admin panel
# URL: https://your-domain.com/{secure_path}
# The secure_path was shown during install
```

### Development Setup

```bash
# Additional dev dependencies
composer install

# Run development server
php artisan serve --host=0.0.0.0 --port=8000

# Run queue worker (development)
php artisan horizon

# Watch logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Run tests
php artisan test
```

---

## 20. Appendix

### Binary Files Inventory

| Path | Size | MIME Type | Purpose |
|------|------|-----------|---------|
| `public/assets/admin/static/*.woff2` | 1-50KB | font/woff2 | Icon fonts |
| `public/assets/admin/static/*.ttf` | 10-100KB | font/ttf | Icon fonts |
| `public/theme/v2board/assets/images/icon/*.png` | 1-30KB | image/png | Client icons |

### External Documentation

- Laravel 8: https://laravel.com/docs/8.x
- Laravel Horizon: https://laravel.com/docs/8.x/horizon
- UMI: https://umijs.org/
- V2Board Official: https://v2board.com/

### Licenses

| Component | License |
|-----------|---------|
| V2Board | MIT |
| Laravel | MIT |
| Firebase JWT | BSD-3-Clause |
| Stripe PHP | MIT |

### TODO Items Found in Code

| File | Line | TODO |
|------|------|------|
| `ServerService.php` | 255-261 | Remove route JSON decode on 1.8.0 |
| `routes/web.php` | 40 | 兼容 (compatibility note) |

### NOT INFERABLE Items

| Item | Reason | Verification |
|------|--------|--------------|
| Frontend source code | Pre-built only | Request from maintainers |
| Telegram bot commands full list | Runtime registration | Test with actual bot |
| Payment gateway test credentials | Security | Request from providers |

---

*End of Analysis Document*
