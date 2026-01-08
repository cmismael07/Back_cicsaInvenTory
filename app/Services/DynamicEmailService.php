<?php

namespace App\Services;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DynamicEmailService
{
    public function sendRaw(string $to, string $subject, string $body, array $cc = []): bool
    {
        $settings = EmailSetting::first();
        if (! $settings) {
            Log::warning('DynamicEmailService: no EmailSetting row, skipping send', ['to' => $to, 'subject' => $subject]);
            return false;
        }

        if (empty($settings->smtp_host) || empty($settings->smtp_username)) {
            Log::warning('DynamicEmailService: SMTP not configured, skipping send', [
                'to' => $to,
                'subject' => $subject,
                'smtp_host' => $settings->smtp_host,
                'smtp_username_set' => !empty($settings->smtp_username),
            ]);
            return false;
        }

        $password = null;
        try {
            $password = $settings->smtp_password ? Crypt::decryptString($settings->smtp_password) : null;
        } catch (\Throwable $e) {
            $password = null;
        }

        $mailerConfig = [
            'transport' => 'smtp',
            'host' => $settings->smtp_host,
            'port' => $settings->smtp_port ?: 587,
            'encryption' => $settings->smtp_encryption ?: null,
            'username' => $settings->smtp_username,
            'password' => $password,
        ];

        config(['mail.mailers.dynamic' => $mailerConfig]);

        $fromAddress = $settings->smtp_username;
        $fromName = $settings->remitente ?: null;

        $cc = array_values(array_filter(array_map('trim', $cc)));

        Log::info('DynamicEmailService: attempting send', [
            'to' => $to,
            'cc_count' => count($cc),
            'subject' => $subject,
            'smtp' => [
                'host' => $settings->smtp_host,
                'port' => $settings->smtp_port ?: 587,
                'encryption' => $settings->smtp_encryption ?: null,
                'username' => $settings->smtp_username,
                'password_set' => !empty($password),
            ],
        ]);

        try {
            Mail::mailer('dynamic')->raw($body, function ($message) use ($to, $cc, $subject, $fromAddress, $fromName) {
                $message->to($to)->subject($subject);
                if (!empty($cc)) {
                    $message->cc($cc);
                }
                if ($fromAddress) {
                    $message->from($fromAddress, $fromName ?: null);
                }
            });

            Log::info('DynamicEmailService: send OK', ['to' => $to, 'subject' => $subject]);
            return true;
        } catch (\Throwable $e) {
            Log::error('DynamicEmailService: send FAILED', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return false;
        }
    }
}
