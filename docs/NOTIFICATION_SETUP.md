# V2Board 通知系统配置指南

本系统已内置完整的通知功能，支持 Telegram、Discord 和邮件通知。无需安装额外插件，只需在数据库中配置几个参数即可。

## 功能介绍

### 管理员通知（发送到 Telegram/Discord/邮件）
- ✅ 新用户注册通知
- ✅ 用户付款成功通知
- ✅ 新工单创建通知
- ✅ 用户工单回复通知

### 用户邮件通知（自动发送给用户）
- ✅ 注册欢迎邮件
- ✅ 订单创建提醒
- ✅ 支付成功确认
- ✅ 工单回复通知

所有通知内容均为**中文**，无需额外配置。

---

## 配置方法

### 方法一：通过数据库配置（推荐）

在 `v2_settings` 表中添加以下配置：

```sql
-- Telegram 机器人 Token
INSERT INTO v2_settings (name, value) VALUES ('telegram_bot_token', '你的Bot Token') 
ON DUPLICATE KEY UPDATE value = '你的Bot Token';

-- Telegram 接收通知的 Chat ID
INSERT INTO v2_settings (name, value) VALUES ('notify_telegram_chat_id', '你的Chat ID') 
ON DUPLICATE KEY UPDATE value = '你的Chat ID';

-- Discord Webhook URL
INSERT INTO v2_settings (name, value) VALUES ('notify_discord_webhook', '你的Discord Webhook URL') 
ON DUPLICATE KEY UPDATE value = '你的Discord Webhook URL';

-- 管理员邮箱（接收邮件通知）
INSERT INTO v2_settings (name, value) VALUES ('notify_admin_email', 'admin@example.com') 
ON DUPLICATE KEY UPDATE value = 'admin@example.com';
```

### 方法二：通过 V2Board 后台配置

1. 登录 V2Board 管理后台
2. 进入「系统配置」→「Telegram」标签页
3. 填写 `Telegram Bot Token`
4. 其他配置需要直接在数据库中添加

---

## 配置详解

### 1. Telegram 配置

#### 获取 Bot Token
1. 在 Telegram 中搜索 `@BotFather`
2. 发送 `/newbot` 创建新机器人
3. 按提示设置机器人名称
4. 获取 Bot Token（格式如：`123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ`）

#### 获取 Chat ID
1. 在 Telegram 中搜索 `@userinfobot`
2. 发送任意消息
3. 机器人会返回你的 Chat ID

或者创建群组：
1. 创建 Telegram 群组
2. 将机器人添加到群组
3. 在群组中发送 `/start`
4. 访问 `https://api.telegram.org/bot{TOKEN}/getUpdates` 获取群组 Chat ID

### 2. Discord 配置

1. 在 Discord 服务器中选择一个频道
2. 右键点击频道 → 「编辑频道」
3. 进入「整合」→「Webhook」
4. 点击「新建 Webhook」
5. 复制 Webhook URL

### 3. 管理员邮件配置

直接填写管理员邮箱地址即可。确保 V2Board 的邮件发送功能已正确配置。

---

## 通知示例

### Telegram/Discord 通知示例

```
🎉 新用户注册
━━━━━━━━━━━━━━━━━━━━
📧 邮箱: user@example.com
🕐 时间: 2024-01-01 12:00:00
```

```
💰 收到新付款
━━━━━━━━━━━━━━━━━━━━
📧 用户: user@example.com
💵 金额: ¥99.00
📦 套餐: 月付套餐
🔢 订单: V2B202401010001
🕐 时间: 2024-01-01 12:00:00
```

```
📮 新工单 #123
━━━━━━━━━━━━━━━━━━━━
📧 用户: user@example.com
📝 主题: 无法连接服务器
💬 内容: 请帮我检查一下...
🕐 时间: 2024-01-01 12:00:00
```

---

## 文件说明

通知系统涉及的文件：

| 文件 | 说明 |
|------|------|
| `app/Services/NotificationService.php` | 通知服务核心，处理所有通知逻辑 |
| `app/Services/OrderService.php` | 订单服务，支付成功时触发通知 |
| `app/Services/TicketService.php` | 工单服务，工单回复时触发通知 |
| `app/Http/Controllers/Passport/AuthController.php` | 注册控制器，新用户注册时触发通知 |
| `app/Http/Controllers/User/OrderController.php` | 用户订单控制器，订单创建时触发通知 |
| `app/Http/Controllers/User/TicketController.php` | 用户工单控制器，工单创建时触发通知 |

---

## 常见问题

### Q: 为什么收不到 Telegram 通知？
A: 请检查：
1. Bot Token 是否正确
2. Chat ID 是否正确
3. 机器人是否已添加到群组（如果使用群组）
4. 确保机器人有发送消息的权限

### Q: 为什么收不到邮件通知？
A: 请检查：
1. V2Board 邮件配置是否正确
2. 管理员邮箱是否正确
3. 查看 `storage/logs/laravel.log` 日志文件

### Q: 如何禁用某种通知？
A: 将对应的配置项设置为空即可。例如，不配置 `notify_discord_webhook` 则不会发送 Discord 通知。

---

## 技术支持

如有问题，请提交 Issue 或联系开发者。
