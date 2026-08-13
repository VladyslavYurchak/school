<?php

namespace App\Services\Telegram;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\TelegramAccount;
use App\Models\TelegramNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramLessonReminderService
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(private readonly TelegramBotClient $bot) {}

    public function sendDueReminders(): array
    {
        $result = [
            'lessons' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        $this->dueLessonsQuery()->chunkById(100, function ($lessons) use (&$result) {
            foreach ($lessons as $lesson) {
                $accounts = $this->telegramAccountsFor($lesson);

                if ($accounts->isEmpty() && $lesson->start_date->gt(now()->addHour())) {
                    continue;
                }

                $result['lessons']++;

                foreach ($accounts as $account) {
                    $status = $this->sendToAccount($lesson, $account);
                    $result[$status]++;
                }
            }
        });

        return $result;
    }

    private function dueLessonsQuery(): Builder
    {
        return PlannedLesson::query()
            ->with([
                'teacher:id,user_id,first_name,last_name,meeting_url',
                'teacher.user.telegramAccount',
                'student.user.telegramAccount',
                'group:id,name',
                'group.students.user.telegramAccount',
            ])
            ->where('status', LessonStatus::Planned->value)
            ->where('start_date', '>', now())
            ->where('start_date', '<=', now()->addDay())
            ->orderBy('id');
    }

    private function telegramAccountsFor(PlannedLesson $lesson): Collection
    {
        $accounts = collect([
            $lesson->teacher?->user?->telegramAccount,
        ]);
        $students = collect();

        if ($lesson->student) {
            $students->push($lesson->student);
        }

        if ($lesson->group) {
            $students = $students->merge($lesson->group->students);
        }

        $studentAccounts = $students
            ->unique('id')
            ->map(fn (Student $student) => $student->user?->telegramAccount)
            ->filter();

        return $accounts
            ->merge($studentAccounts)
            ->filter(fn (?TelegramAccount $account) => $account?->notifications_enabled === true
                && $account->lesson_reminders_enabled === true
                && $lesson->start_date->lte(
                    now()->addMinutes((int) $account->lesson_reminder_minutes),
                ))
            ->unique('id')
            ->values();
    }

    private function sendToAccount(
        PlannedLesson $lesson,
        TelegramAccount $account,
    ): string {
        $notification = TelegramNotification::query()->firstOrCreate(
            [
                'telegram_account_id' => $account->id,
                'planned_lesson_id' => $lesson->id,
                'type' => TelegramNotification::TYPE_LESSON_REMINDER,
            ],
            ['status' => TelegramNotification::STATUS_PENDING],
        );

        if (! $this->claim($notification)) {
            return 'skipped';
        }

        try {
            $keyboard = $this->keyboardFor($lesson, $account);
            $options = $keyboard === [] ? [] : [
                'reply_markup' => ['inline_keyboard' => $keyboard],
            ];
            $sent = $this->bot->sendMessage(
                $account->chat_id,
                $this->messageFor($lesson, $account),
                $options,
            );
        } catch (Throwable $exception) {
            Log::warning('Telegram lesson reminder raised an exception.', [
                'lesson_id' => $lesson->id,
                'notification_id' => $notification->id,
                'exception' => $exception::class,
            ]);

            $sent = false;
        }

        $notification->update([
            'status' => $sent
                ? TelegramNotification::STATUS_SENT
                : TelegramNotification::STATUS_FAILED,
            'sent_at' => $sent ? now() : null,
        ]);

        return $sent ? 'sent' : 'failed';
    }

    private function claim(TelegramNotification $notification): bool
    {
        if ($notification->status === TelegramNotification::STATUS_SENT
            || $notification->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        return TelegramNotification::query()
            ->whereKey($notification->id)
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->where(function (Builder $query) {
                $query
                    ->whereIn('status', [
                        TelegramNotification::STATUS_PENDING,
                        TelegramNotification::STATUS_FAILED,
                    ])
                    ->orWhere(function (Builder $query) {
                        $query
                            ->where('status', TelegramNotification::STATUS_PROCESSING)
                            ->where('last_attempt_at', '<=', now()->subMinutes(5));
                    });
            })
            ->update([
                'status' => TelegramNotification::STATUS_PROCESSING,
                'attempts' => DB::raw('attempts + 1'),
                'last_attempt_at' => now(),
            ]) === 1;
    }

    private function messageFor(
        PlannedLesson $lesson,
        TelegramAccount $account,
    ): string {
        $isTeacher = $lesson->teacher?->user_id === $account->user_id;
        $lines = [
            '<b>Нагадування про заняття</b>',
            '',
            $isTeacher
                ? 'Ваше заняття заплановано найближчим часом.'
                : 'Нагадуємо про ваше заплановане заняття.',
            '<b>Заняття:</b> '.$this->escape($lesson->title),
            '<b>Дата і час:</b> '.$lesson->start_date->format('d.m.Y H:i'),
            '<b>Формат:</b> '.$this->lessonTypeLabel($lesson->lesson_type),
        ];

        if ($isTeacher) {
            if ($lesson->group) {
                $lines[] = '<b>Група:</b> '.$this->escape($lesson->group->name);
            } elseif ($lesson->student) {
                $lines[] = '<b>Учень:</b> '.$this->escape($lesson->student->full_name);
            } else {
                $lines[] = '<b>Учень:</b> пробне заняття без прив’язаного учня';
            }
        } else {
            $teacherName = trim(
                ($lesson->teacher?->first_name ?? '').' '.($lesson->teacher?->last_name ?? ''),
            );

            if ($teacherName !== '') {
                $lines[] = '<b>Викладач:</b> '.$this->escape($teacherName);
            }

            if ($lesson->group) {
                $lines[] = '<b>Група:</b> '.$this->escape($lesson->group->name);
            }
        }

        return implode("\n", $lines);
    }

    private function keyboardFor(PlannedLesson $lesson, TelegramAccount $account): array
    {
        $row = [];

        if ($lesson->effective_meeting_url) {
            $row[] = [
                'text' => 'Приєднатися',
                'url' => $lesson->effective_meeting_url,
            ];
        }

        $isTeacher = $lesson->teacher?->user_id === $account->user_id;

        if (! $isTeacher) {
            $row[] = [
                'text' => 'Не зможу бути',
                'callback_data' => "student:absence:{$lesson->id}",
            ];
        }

        return $row === [] ? [] : [$row];
    }

    private function lessonTypeLabel(LessonType $type): string
    {
        return match ($type) {
            LessonType::Individual => 'Індивідуальне',
            LessonType::Group => 'Групове',
            LessonType::Pair => 'Парне',
            LessonType::Trial => 'Пробне',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
