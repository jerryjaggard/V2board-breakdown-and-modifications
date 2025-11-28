<img src="https://avatars.githubusercontent.com/u/56885001?s=200&v=4" alt="logo" width="130" height="130" align="right"/>

# **V2Board** (带通知系统增强版)

本版本集成了完整的通知系统，支持 Telegram、Discord 和邮件通知。

## 系统要求

- PHP7.3+
- Composer
- MySQL5.5+
- Redis
- Laravel

## 内置通知功能

### 管理员通知（Telegram/Discord/邮件）
- ✅ 新用户注册
- ✅ 用户付款成功
- ✅ 新工单/工单回复

### 用户邮件通知
- ✅ 欢迎邮件
- ✅ 订单/支付确认
- ✅ 工单回复通知

所有通知内容均为**中文**。

## 快速配置

在数据库 `v2_settings` 表中添加以下配置：

```sql
-- Telegram Bot Token
INSERT INTO v2_settings (name, value) VALUES ('telegram_bot_token', '你的Bot Token');

-- Telegram Chat ID
INSERT INTO v2_settings (name, value) VALUES ('notify_telegram_chat_id', '你的Chat ID');

-- Discord Webhook (可选)
INSERT INTO v2_settings (name, value) VALUES ('notify_discord_webhook', '你的Webhook URL');

-- 管理员邮箱 (可选)
INSERT INTO v2_settings (name, value) VALUES ('notify_admin_email', 'admin@example.com');
```

**详细配置说明：** [docs/NOTIFICATION_SETUP.md](docs/NOTIFICATION_SETUP.md)

## Demo
[Demo](https://demo.v2board.com)

## Document
[Click](https://v2board.com)

## Sponsors
Thanks to the open source project license provided by [Jetbrains](https://www.jetbrains.com/)

## Community
🔔Telegram Channel: [@v2board](https://t.me/v2board)  

## How to Feedback
Follow the template in the issue to submit your question correctly, and we will have someone follow up with you.
