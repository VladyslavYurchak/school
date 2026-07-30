<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:webhook:set';

    protected $description = 'Register the application Telegram webhook';

    public function handle(): int
    {
        $token = trim((string) config('services.telegram.bot_token'));
        $secret = trim((string) config('services.telegram.webhook_secret'));
        $webhookUrl = route('telegram.webhook');

        if ($token === '') {
            $this->error('TELEGRAM_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        if (! preg_match('/^[A-Za-z0-9_-]{1,256}$/', $secret)) {
            $this->error('TELEGRAM_WEBHOOK_SECRET must contain only A-Z, a-z, 0-9, _ or -.');

            return self::FAILURE;
        }

        if (! str_starts_with($webhookUrl, 'https://')) {
            $this->error('Telegram webhook URL must use HTTPS. Check APP_URL.');

            return self::FAILURE;
        }

        $response = Http::asJson()
            ->timeout(15)
            ->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $webhookUrl,
                'secret_token' => $secret,
                'allowed_updates' => ['message', 'callback_query'],
                'drop_pending_updates' => false,
            ]);

        if ($response->failed() || ! $response->json('ok')) {
            $this->error('Telegram rejected the webhook configuration.');
            $description = $response->json('description');

            if (is_string($description) && $description !== '') {
                $this->line($description);
            }

            return self::FAILURE;
        }

        $this->info("Telegram webhook registered: {$webhookUrl}");

        return self::SUCCESS;
    }
}
