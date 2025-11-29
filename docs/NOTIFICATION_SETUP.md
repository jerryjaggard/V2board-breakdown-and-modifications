# V2Board 通知系统配置指南

本系统为 V2Board 集成了自动通知功能，支持通过 Telegram、Discord 和邮件向管理员发送通知，同时支持向用户发送中文邮件通知。

## 配置方法

编辑项目根目录的 `.env` 文件，添加以下配置：

```env
# Telegram 机器人 Token (从 @BotFather 获取)
V2BOARD_TELEGRAM_BOT_TOKEN=你的Bot_Token

# Telegram Chat ID (发送 /getid 给 @userinfobot 获取你的 Chat ID)
V2BOARD_NOTIFY_TELEGRAM_CHAT_ID=你的Chat_ID

# Discord Webhook URL (可选)
V2BOARD_NOTIFY_DISCORD_WEBHOOK=你的Discord_Webhook_URL

# 管理员邮箱 (可选，用于接收邮件通知)
V2BOARD_NOTIFY_ADMIN_EMAIL=admin@example.com
```

## 获取 Telegram Bot Token

1. 在 Telegram 中搜索 `@BotFather`
2. 发送 `/newbot`
3. 按照提示设置机器人名称
4. 获取 Token，格式类似：`1234567890:ABCdefGhIjKlMnOpQrStUvWxYz`

## 获取 Telegram Chat ID

1. 在 Telegram 中搜索 `@userinfobot`
2. 发送 `/start`
3. 机器人会返回你的 Chat ID

## 获取 Discord Webhook URL

1. 进入 Discord 服务器设置
2. 选择 "整合" -> "Webhook"
3. 创建新 Webhook
4. 复制 Webhook URL

## 通知功能

### 管理员通知（发送到 Telegram/Discord/邮箱）

- 🎉 新用户注册
- 💰 用户付款成功
- 📮 新工单创建
- 📮 工单用户回复

### 用户邮件通知（中文内容）

- 欢迎邮件（注册成功后）
- 订单创建提醒
- 支付成功确认
- 工单回复通知（管理员回复后）

## 通知示例

### Telegram/Discord 通知格式

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

## 核心文件

| 文件路径 | 说明 |
|---------|------|
| `.env` | 通知配置（Telegram/Discord/邮箱） |
| `app/Services/NotificationService.php` | 通知服务核心代码 |
| `app/Services/OrderService.php` | 支付成功触发通知 |
| `app/Services/TicketService.php` | 工单回复触发通知 |

## 注意事项

1. 配置修改后立即生效，无需重启
2. 如果某个通知渠道未配置，该渠道的通知将被跳过
3. 所有通知内容均为中文
4. 通知发送失败不会影响正常业务流程
