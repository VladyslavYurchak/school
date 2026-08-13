<?php

namespace App\Services\Telegram;

use App\Models\Payment;
use App\Models\TelegramAccount;
use App\Models\TelegramPaymentConfirmation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramPaymentConfirmationService
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(private readonly TelegramBotClient $bot) {}

    public function sendForPayment(Payment $payment): string
    {
        if ($payment->status !== 'paid') {
            return 'skipped';
        }

        $payment->loadMissing('student.user.telegramAccount');
        $account = $payment->student?->user?->telegramAccount;

        if (! $this->canNotify($account)) {
            return 'skipped';
        }

        $confirmation = TelegramPaymentConfirmation::query()->firstOrCreate(
            ['payment_id' => $payment->id],
            [
                'telegram_account_id' => $account->id,
                'status' => TelegramPaymentConfirmation::STATUS_PENDING,
            ],
        );

        return $this->send($confirmation, $payment, $account);
    }

    public function retryPending(): array
    {
        $result = ['confirmations' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        TelegramPaymentConfirmation::query()
            ->with(['payment.student.user.telegramAccount', 'telegramAccount'])
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function (Builder $query) {
                $query
                    ->whereIn('status', [
                        TelegramPaymentConfirmation::STATUS_PENDING,
                        TelegramPaymentConfirmation::STATUS_FAILED,
                    ])
                    ->orWhere(function (Builder $query) {
                        $query
                            ->where('status', TelegramPaymentConfirmation::STATUS_PROCESSING)
                            ->where('last_attempt_at', '<=', now()->subMinutes(5));
                    });
            })
            ->chunkById(100, function ($confirmations) use (&$result) {
                foreach ($confirmations as $confirmation) {
                    $result['confirmations']++;
                    $payment = $confirmation->payment;
                    $account = $confirmation->telegramAccount;

                    if (! $payment || ! $this->canNotify($account)) {
                        $result['skipped']++;

                        continue;
                    }

                    $result[$this->send($confirmation, $payment, $account)]++;
                }
            });

        return $result;
    }

    private function send(
        TelegramPaymentConfirmation $confirmation,
        Payment $payment,
        TelegramAccount $account,
    ): string {
        if (! $this->claim($confirmation)) {
            return 'skipped';
        }

        try {
            $sent = $this->bot->sendMessage(
                $account->chat_id,
                $this->messageFor($payment),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => 'Мої платежі',
                                'url' => route('student.payments.index'),
                            ],
                        ]],
                    ],
                ],
            );
        } catch (Throwable $exception) {
            Log::warning('Telegram payment confirmation raised an exception.', [
                'payment_id' => $payment->id,
                'confirmation_id' => $confirmation->id,
                'exception' => $exception::class,
            ]);

            $sent = false;
        }

        $confirmation->update([
            'status' => $sent
                ? TelegramPaymentConfirmation::STATUS_SENT
                : TelegramPaymentConfirmation::STATUS_FAILED,
            'sent_at' => $sent ? now() : null,
        ]);

        return $sent ? 'sent' : 'failed';
    }

    private function claim(TelegramPaymentConfirmation $confirmation): bool
    {
        if ($confirmation->status === TelegramPaymentConfirmation::STATUS_SENT
            || $confirmation->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        return TelegramPaymentConfirmation::query()
            ->whereKey($confirmation->id)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function (Builder $query) {
                $query
                    ->whereIn('status', [
                        TelegramPaymentConfirmation::STATUS_PENDING,
                        TelegramPaymentConfirmation::STATUS_FAILED,
                    ])
                    ->orWhere(function (Builder $query) {
                        $query
                            ->where('status', TelegramPaymentConfirmation::STATUS_PROCESSING)
                            ->where('last_attempt_at', '<=', now()->subMinutes(5));
                    });
            })
            ->update([
                'status' => TelegramPaymentConfirmation::STATUS_PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'last_attempt_at' => now(),
            ]) === 1;
    }

    private function canNotify(?TelegramAccount $account): bool
    {
        return $account?->notifications_enabled === true
            && $account->payment_notifications_enabled === true;
    }

    private function messageFor(Payment $payment): string
    {
        $payload = is_array($payment->payload) ? $payment->payload : [];
        $lines = [
            '<b>Оплату успішно отримано</b>',
            '',
            '<b>Сума:</b> '.number_format((float) $payment->amount, 2, '.', ' ').' '.$this->escape($payment->currency),
        ];

        if ($month = ($payload['subscription_month'] ?? null)) {
            try {
                $period = Carbon::createFromFormat('!Y-m', (string) $month)
                    ->locale('uk')
                    ->translatedFormat('F Y');
                $lines[] = '<b>Період:</b> '.$this->escape($period);
            } catch (Throwable) {
                // The payment is already valid; omit only an invalid optional label.
            }
        } elseif ($payment->description) {
            $lines[] = '<b>Призначення:</b> '.$this->escape($payment->description);
        }

        $lines[] = '';
        $lines[] = 'Дякуємо! Статус оплати та доступи вже оновлено.';

        return implode("\n", $lines);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
