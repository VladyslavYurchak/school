<?php

namespace App\Services\Telegram;

use App\Models\TelegramAccount;
use App\Models\TelegramLinkToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelegramLinkService
{
    public function issue(User $user): string
    {
        abort_unless($user->isStudent() || $user->hasActiveTeacherProfile(), 403);

        $plainToken = Str::random(48);

        DB::transaction(function () use ($user, $plainToken) {
            TelegramLinkToken::query()
                ->where('user_id', $user->id)
                ->delete();

            TelegramLinkToken::query()->create([
                'user_id' => $user->id,
                'token_hash' => hash('sha256', $plainToken),
                'expires_at' => now()->addMinutes(15),
            ]);
        });

        return $plainToken;
    }

    /**
     * @return 'connected'|'invalid'|'telegram_conflict'|'user_conflict'
     */
    public function connect(string $plainToken, array $telegramUser, string $chatId): string
    {
        $telegramUserId = trim((string) ($telegramUser['id'] ?? ''));
        $chatId = trim($chatId);

        if ($plainToken === '' || $telegramUserId === '' || $chatId === '') {
            return 'invalid';
        }

        return DB::transaction(function () use ($plainToken, $telegramUser, $telegramUserId, $chatId) {
            $linkToken = TelegramLinkToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->lockForUpdate()
                ->first();

            if (
                ! $linkToken
                || $linkToken->used_at
                || $linkToken->expires_at->isPast()
                || ! $linkToken->user
                || (! $linkToken->user->isStudent() && ! $linkToken->user->hasActiveTeacherProfile())
            ) {
                return 'invalid';
            }

            $telegramOwner = TelegramAccount::query()
                ->where(function ($query) use ($telegramUserId, $chatId) {
                    $query->where('telegram_user_id', $telegramUserId)
                        ->orWhere('chat_id', $chatId);
                })
                ->lockForUpdate()
                ->first();

            if ($telegramOwner && $telegramOwner->user_id !== $linkToken->user_id) {
                return 'telegram_conflict';
            }

            $userAccount = TelegramAccount::query()
                ->where('user_id', $linkToken->user_id)
                ->lockForUpdate()
                ->first();

            if (
                $userAccount
                && (
                    $userAccount->telegram_user_id !== $telegramUserId
                    || $userAccount->chat_id !== $chatId
                )
            ) {
                return 'user_conflict';
            }

            TelegramAccount::query()->updateOrCreate(
                ['user_id' => $linkToken->user_id],
                [
                    'telegram_user_id' => $telegramUserId,
                    'chat_id' => $chatId,
                    'username' => $telegramUser['username'] ?? null,
                    'first_name' => $telegramUser['first_name'] ?? null,
                    'notifications_enabled' => true,
                    'connected_at' => $userAccount?->connected_at ?? now(),
                    'last_interaction_at' => now(),
                ]
            );

            $linkToken->update(['used_at' => now()]);

            return 'connected';
        });
    }
}
