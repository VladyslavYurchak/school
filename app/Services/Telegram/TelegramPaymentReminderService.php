<?php

namespace App\Services\Telegram;

use App\Models\Student;
use App\Models\TelegramAccount;
use App\Models\TelegramPaymentReminder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramPaymentReminderService
{
    private const MAX_ATTEMPTS = 3;

    private const UPCOMING_DAYS = 7;

    public function __construct(private readonly TelegramBotClient $bot) {}

    public function sendDueReminders(): array
    {
        $result = ['reminders' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];
        $currentMonth = now()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        Student::query()
            ->with([
                'subscriptionTemplate',
                'subscriptions' => fn ($query) => $query
                    ->where('type', 'subscription')
                    ->whereIn('status', ['active', 'expired'])
                    ->whereBetween('start_date', [
                        $currentMonth->toDateString(),
                        $nextMonth->copy()->endOfMonth()->toDateString(),
                    ]),
                'user.telegramAccount',
            ])
            ->where('is_active', true)
            ->whereNotNull('subscription_id')
            ->whereHas('user.telegramAccount', fn (Builder $query) => $query
                ->where('notifications_enabled', true)
                ->where('payment_notifications_enabled', true))
            ->chunkById(100, function ($students) use (&$result, $currentMonth, $nextMonth) {
                foreach ($students as $student) {
                    $account = $student->user?->telegramAccount;

                    if (! $account) {
                        continue;
                    }

                    [$month, $stage] = $this->reminderTarget($student, $currentMonth, $nextMonth);

                    if (! $month || ! $stage) {
                        continue;
                    }

                    $result['reminders']++;
                    $status = $this->sendToAccount($student, $account, $month, $stage);
                    $result[$status]++;
                }
            });

        return $result;
    }

    private function reminderTarget(Student $student, Carbon $currentMonth, Carbon $nextMonth): array
    {
        if (! $this->hasPaidMonth($student, $currentMonth)) {
            return [$currentMonth, TelegramPaymentReminder::STAGE_DUE];
        }

        if (
            now()->gte($nextMonth->copy()->subDays(self::UPCOMING_DAYS))
            && ! $this->hasPaidMonth($student, $nextMonth)
        ) {
            return [$nextMonth, TelegramPaymentReminder::STAGE_UPCOMING];
        }

        return [null, null];
    }

    private function hasPaidMonth(Student $student, Carbon $month): bool
    {
        return $student->subscriptions->contains(
            fn ($subscription) => $subscription->start_date->isSameMonth($month),
        );
    }

    private function sendToAccount(
        Student $student,
        TelegramAccount $account,
        Carbon $month,
        string $stage,
    ): string {
        $reminder = TelegramPaymentReminder::query()->firstOrCreate(
            [
                'telegram_account_id' => $account->id,
                'payment_month' => $month->toDateString(),
                'stage' => $stage,
            ],
            [
                'student_id' => $student->id,
                'status' => TelegramPaymentReminder::STATUS_PENDING,
            ],
        );

        if (! $this->claim($reminder)) {
            return 'skipped';
        }

        try {
            $sent = $this->bot->sendMessage(
                $account->chat_id,
                $this->messageFor($student, $month, $stage),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => 'Перейти до оплати',
                                'url' => route('student.payments.index'),
                            ],
                        ]],
                    ],
                ],
            );
        } catch (Throwable $exception) {
            Log::warning('Telegram payment reminder raised an exception.', [
                'student_id' => $student->id,
                'reminder_id' => $reminder->id,
                'exception' => $exception::class,
            ]);

            $sent = false;
        }

        $reminder->update([
            'status' => $sent
                ? TelegramPaymentReminder::STATUS_SENT
                : TelegramPaymentReminder::STATUS_FAILED,
            'sent_at' => $sent ? now() : null,
        ]);

        return $sent ? 'sent' : 'failed';
    }

    private function claim(TelegramPaymentReminder $reminder): bool
    {
        if ($reminder->status === TelegramPaymentReminder::STATUS_SENT
            || $reminder->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        return TelegramPaymentReminder::query()
            ->whereKey($reminder->id)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function (Builder $query) {
                $query
                    ->whereIn('status', [
                        TelegramPaymentReminder::STATUS_PENDING,
                        TelegramPaymentReminder::STATUS_FAILED,
                    ])
                    ->orWhere(function (Builder $query) {
                        $query
                            ->where('status', TelegramPaymentReminder::STATUS_PROCESSING)
                            ->where('last_attempt_at', '<=', now()->subMinutes(5));
                    });
            })
            ->update([
                'status' => TelegramPaymentReminder::STATUS_PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'last_attempt_at' => now(),
            ]) === 1;
    }

    private function messageFor(Student $student, Carbon $month, string $stage): string
    {
        $template = $student->subscriptionTemplate;
        $period = $month->copy()->locale('uk')->translatedFormat('F Y');
        $title = $template?->title ?: 'Абонемент';
        $price = number_format((float) ($template?->price ?? 0), 2, '.', ' ');

        $intro = $stage === TelegramPaymentReminder::STAGE_UPCOMING
            ? 'Нагадуємо про оплату навчання на наступний місяць.'
            : 'Оплата навчання за цей місяць ще не зафіксована.';

        return implode("\n", [
            '<b>Нагадування про оплату</b>',
            '',
            $intro,
            '<b>Період:</b> '.$this->escape($period),
            '<b>Абонемент:</b> '.$this->escape($title),
            '<b>Сума:</b> '.$price.' грн',
            '',
            'Натисніть кнопку нижче, щоб перейти до оплати.',
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
