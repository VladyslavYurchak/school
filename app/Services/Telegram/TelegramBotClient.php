<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotClient
{
    public function sendMessage(string $chatId, string $text, array $options = []): bool
    {
        $token = trim((string) config('services.telegram.bot_token'));

        if ($token === '' || $chatId === '') {
            return false;
        }

        $response = Http::asJson()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
                'link_preview_options' => ['is_disabled' => true],
            ], $options));

        if ($response->failed()) {
            Log::warning('Telegram sendMessage failed.', [
                'status' => $response->status(),
            ]);
        }

        return $response->successful() && $response->json('ok') === true;
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null): bool
    {
        $payload = ['callback_query_id' => $callbackQueryId];

        if ($text !== null && $text !== '') {
            $payload['text'] = $text;
        }

        return $this->post('answerCallbackQuery', $payload);
    }

    public function removeInlineKeyboard(string $chatId, int $messageId): bool
    {
        return $this->post('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => ['inline_keyboard' => []],
        ]);
    }

    private function post(string $method, array $payload): bool
    {
        $token = trim((string) config('services.telegram.bot_token'));

        if ($token === '') {
            return false;
        }

        $response = Http::asJson()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/{$method}", $payload);

        if ($response->failed()) {
            Log::warning("Telegram {$method} failed.", [
                'status' => $response->status(),
            ]);
        }

        return $response->successful() && $response->json('ok') === true;
    }
}
