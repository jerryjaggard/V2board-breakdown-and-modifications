<?php
/**
 * TicketMailer - Ticket Email Notifications Plugin for XBoard
 *
 * v1.0.0
 * - Sends email to user when a ticket is created (confirmation + full content)
 * - Sends email to user when an admin replies (latest reply + context)
 * - Supports language modes: en / zh / en_zh / auto (similar logic as UserMailer)
 * - Uses SendEmailJob with template "notify"
 */

namespace Plugin\TicketMailer;

use App\Models\User;
use App\Models\Ticket;
use App\Services\Plugin\AbstractPlugin;
use App\Jobs\SendEmailJob;
use Illuminate\Support\Facades\Log;

class Plugin extends AbstractPlugin
{
    public function boot(): void
    {
        if (!$this->getConfig('enabled', true)) {
            return;
        }

        $this->registerHooks();
    }

    protected function registerHooks(): void
    {
        if ($this->getConfig('send_ticket_created', true)) {
            $this->listen('ticket.create.after', [$this, 'handleTicketCreated']);
        }

        if ($this->getConfig('send_admin_reply', true)) {
            // Expected signature from your app: ($ticket, $reply)
            $this->listen('ticket.reply.after', [$this, 'handleTicketReply']);
        }
    }

    /* ========== LANGUAGE HELPERS ========== */

    protected function getGlobalEmailLanguageMode(): string
    {
        $mode = strtolower((string) $this->getConfig('email_language_mode', 'auto'));
        if (!in_array($mode, ['en', 'zh', 'en_zh', 'auto'], true)) {
            $mode = 'auto';
        }
        return $mode;
    }

    protected function getEmailLanguageModeForUser(?User $user): string
    {
        $global = $this->getGlobalEmailLanguageMode();

        if ($global !== 'auto') {
            return $global;
        }

        if (!$user || !$user->email) {
            return 'en';
        }

        $email = strtolower($user->email);
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return 'en';
        }

        $cnDomains = [
            'qq.com',
            '163.com',
            '126.com',
            '139.com',
            '189.cn',
            'sina.com',
            'sina.cn',
            'aliyun.com',
            'aliyun.cn',
            'yeah.net',
            'sohu.com',
            'vip.qq.com',
            'vip.163.com',
        ];

        if (in_array($domain, $cnDomains, true)) {
            return 'zh';
        }

        return 'en';
    }

    protected function buildBilingualForUser(
        ?User $user,
        string $baseKey,
        string $defaultEn,
        string $defaultZh,
        array $vars = []
    ): string {
        $mode = $this->getEmailLanguageModeForUser($user);

        $enCfgKey = $baseKey;
        $zhCfgKey = $baseKey . '_zh';

        $en = $this->getConfig($enCfgKey, $defaultEn);
        $zh = $this->getConfig($zhCfgKey, $defaultZh);

        foreach ($vars as $k => $v) {
            $en = str_replace($k, $v, $en);
            $zh = str_replace($k, $v, $zh);
        }

        return match ($mode) {
            'en'    => $en,
            'zh'    => $zh,
            'en_zh' => trim($en) . "\n\n" . trim($zh),
            default => trim($en) . "\n\n" . trim($zh),
        };
    }

    protected function buildContent(string $configKey, string $default, array $vars = []): string
    {
        $content = $this->getConfig($configKey, $default);
        foreach ($vars as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        return $content;
    }

    protected function buildBilingualContentForUser(
        ?User $user,
        string $baseKey,
        string $defaultEn,
        string $defaultZh,
        array $vars = []
    ): string {
        $mode = $this->getEmailLanguageModeForUser($user);

        $enKey = $baseKey;
        $zhKey = $baseKey . '_zh';

        $en = $this->buildContent($enKey, $defaultEn, $vars);
        $zh = $this->buildContent($zhKey, $defaultZh, $vars);

        return match ($mode) {
            'en'    => $en,
            'zh'    => $zh,
            'en_zh' => trim($en) . "\n\n" . trim($zh),
            default => trim($en) . "\n\n" . trim($zh),
        };
    }

    /* ========== FOOTER ========== */

    protected function buildFooter(?User $user = null): string
    {
        if (!$this->getConfig('contact_footer_enabled', true)) {
            return '';
        }

        $mode = $this->getEmailLanguageModeForUser($user);

        $supportEmail  = trim((string) $this->getConfig('contact_support_email', ''));
        $website       = trim((string) $this->getConfig('contact_website_url', ''));
        $backupWebsite = trim((string) $this->getConfig('contact_backup_website_url', ''));

        $linesEn = [];
        $linesZh = [];

        $linesEn[] = "------------------------------";
        $linesEn[] = "If you had any trouble sending this ticket, or if you need urgent help, you can reach us here:";
        if ($supportEmail !== '') {
            $linesEn[] = "• Support Email: {$supportEmail}";
        }
        if ($website !== '') {
            $linesEn[] = "• Website: {$website}";
        }
        if ($backupWebsite !== '') {
            $linesEn[] = "• Backup site (if main site is blocked): {$backupWebsite}";
        }

        $linesZh[] = "------------------------------";
        $linesZh[] = "如果你在提交工单时遇到问题，或者需要更紧急的帮助，也可以通过以下方式联系我们：";
        if ($supportEmail !== '') {
            $linesZh[] = "• 客服邮箱：{$supportEmail}";
        }
        if ($website !== '') {
            $linesZh[] = "• 官网地址：{$website}";
        }
        if ($backupWebsite !== '') {
            $linesZh[] = "• 备用站点：{$backupWebsite}";
        }

        $footerEn = implode("\n", $linesEn);
        $footerZh = implode("\n", $linesZh);

        return match ($mode) {
            'en'    => "\n\n" . $footerEn,
            'zh'    => "\n\n" . $footerZh,
            'en_zh' => "\n\n" . $footerEn . "\n\n" . $footerZh,
            default => "\n\n" . $footerEn . "\n\n" . $footerZh,
        };
    }

    /* ========== EVENT HANDLERS ========== */

    public function handleTicketCreated($ticket): void
    {
        try {
            /** @var Ticket $ticket */
            $user = User::find($ticket->user_id ?? null);
            if (!$user || !$user->email) {
                return;
            }

            $subject = $this->buildBilingualForUser(
                $user,
                'ticket_created_subject',
                'We received your support ticket',
                '你的工单已收到，我们正在处理',
                []
            );

            $content = $this->buildTicketCreatedEmail($ticket, $user);

            $this->sendEmail($user, $user->email, $subject, $content);

            Log::info('TicketMailer: Sent ticket created email', [
                'ticket_id' => $ticket->id ?? null,
                'email' => $user->email
            ]);
        } catch (\Throwable $e) {
            Log::error('TicketMailer: Failed to send ticket created email', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Expected event signature: ($ticket, $reply)
     */
    public function handleTicketReply($ticket, $reply = null): void
    {
        try {
            if (!$this->getConfig('send_admin_reply', true)) {
                return;
            }

            $user = User::find($ticket->user_id ?? null);
            if (!$user || !$user->email) {
                return;
            }

            // Only send when this is an admin reply (basic heuristic)
            if ($reply && property_exists($reply, 'is_admin') && !$reply->is_admin) {
                return;
            }

            $subject = $this->buildBilingualForUser(
                $user,
                'admin_reply_subject',
                'New reply to your support ticket',
                '你的工单有新的回复',
                []
            );

            $content = $this->buildAdminReplyEmail($ticket, $reply, $user);

            $this->sendEmail($user, $user->email, $subject, $content);

            Log::info('TicketMailer: Sent admin reply email', [
                'ticket_id' => $ticket->id ?? null,
                'email' => $user->email
            ]);
        } catch (\Throwable $e) {
            Log::error('TicketMailer: Failed to send admin reply email', ['error' => $e->getMessage()]);
        }
    }

    /* ========== SEND EMAIL ========== */

    protected function sendEmail(?User $user, string $to, string $subject, string $content): void
    {
        $siteName     = $this->getConfig('site_name', 'Netflare');
        $dashboardUrl = $this->getConfig('dashboard_url', 'https://www.netflare.co/console');

        $footer      = $this->buildFooter($user);
        $fullContent = rtrim($content) . $footer;

        dispatch(new SendEmailJob([
            'email' => $to,
            'subject' => "[{$siteName}] {$subject}",
            'template_name' => 'notify',
            'template_value' => [
                'name'    => 'Customer',
                'content' => $fullContent,
                'url'     => $dashboardUrl,
            ]
        ]));
    }

    /* ========== CONTENT BUILDERS ========== */

    protected function normalizeTicketContent($ticket): string
    {
        $candidates = [];

        if (!empty($ticket->content)) {
            $candidates[] = $ticket->content;
        }
        if (!empty($ticket->message)) {
            $candidates[] = $ticket->message;
        }
        if (!empty($ticket->last_reply)) {
            $candidates[] = $ticket->last_reply;
        }
        if (!empty($ticket->latest_reply)) {
            $candidates[] = $ticket->latest_reply;
        }

        foreach ($candidates as $raw) {
            if (!is_string($raw)) {
                $raw = json_encode($raw, JSON_UNESCAPED_UNICODE);
            }
            $text = trim($raw);
            if ($text !== '') {
                return $text;
            }
        }

        return '(No content)';
    }

    protected function getReplyContent($reply, $ticket): string
    {
        if ($reply) {
            if (!empty($reply->content)) {
                return is_string($reply->content)
                    ? trim($reply->content)
                    : json_encode($reply->content, JSON_UNESCAPED_UNICODE);
            }
            if (!empty($reply->message)) {
                return is_string($reply->message)
                    ? trim($reply->message)
                    : json_encode($reply->message, JSON_UNESCAPED_UNICODE);
            }
        }

        if (!empty($ticket->last_reply)) {
            return is_string($ticket->last_reply)
                ? trim($ticket->last_reply)
                : json_encode($ticket->last_reply, JSON_UNESCAPED_UNICODE);
        }
        if (!empty($ticket->latest_reply)) {
            return is_string($ticket->latest_reply)
                ? trim($ticket->latest_reply)
                : json_encode($ticket->latest_reply, JSON_UNESCAPED_UNICODE);
        }

        return '(No reply content)';
    }

    protected function buildTicketCreatedEmail($ticket, $user): string
    {
        $ticketId   = $ticket->id ?? '';
        $subject    = $ticket->subject ?? '(no subject)';
        $contentRaw = $this->normalizeTicketContent($ticket);
        $centerUrl  = $this->getConfig('ticket_center_url', $this->getConfig('dashboard_url', '') . '/ticket');

        return $this->buildBilingualContentForUser(
            $user,
            'ticket_created_content',
            $this->getConfig('ticket_created_content', ''),
            $this->getConfig('ticket_created_content_zh', ''),
            [
                '{ticket_id}'         => $ticketId,
                '{ticket_subject}'    => $subject,
                '{ticket_content}'    => $contentRaw,
                '{ticket_center_url}' => $centerUrl,
            ]
        );
    }

    protected function buildAdminReplyEmail($ticket, $reply, $user): string
    {
        $ticketId   = $ticket->id ?? '';
        $subject    = $ticket->subject ?? '(no subject)';
        $replyText  = $this->getReplyContent($reply, $ticket);
        $centerUrl  = $this->getConfig('ticket_center_url', $this->getConfig('dashboard_url', '') . '/ticket');

        return $this->buildBilingualContentForUser(
            $user,
            'admin_reply_content',
            $this->getConfig('admin_reply_content', ''),
            $this->getConfig('admin_reply_content_zh', ''),
            [
                '{ticket_id}'         => $ticketId,
                '{ticket_subject}'    => $subject,
                '{reply_content}'     => $replyText,
                '{ticket_center_url}' => $centerUrl,
            ]
        );
    }
}