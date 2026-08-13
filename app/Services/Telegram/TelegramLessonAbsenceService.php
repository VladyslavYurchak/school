<?php

namespace App\Services\Telegram;

use App\Enums\LessonStatus;
use App\Models\PlannedLesson;
use App\Models\TelegramAccount;
use App\Models\TelegramLessonAbsenceRequest;

class TelegramLessonAbsenceService
{
    public function __construct(private readonly TelegramBotClient $bot) {}

    public function request(TelegramAccount $account, int $lessonId): string
    {
        $student = $account->user?->student;

        if (! $student) {
            return 'Ця дія доступна лише учням.';
        }

        $lesson = PlannedLesson::query()
            ->with(['teacher.user.telegramAccount', 'group.students'])
            ->whereKey($lessonId)
            ->where('status', LessonStatus::Planned->value)
            ->where('start_date', '>', now())
            ->first();

        if (! $lesson || ! $this->studentBelongsToLesson($lesson, $student->id)) {
            return 'Заняття не знайдено або воно вже недоступне.';
        }

        $request = TelegramLessonAbsenceRequest::query()->firstOrCreate(
            [
                'planned_lesson_id' => $lesson->id,
                'student_id' => $student->id,
            ],
            [
                'telegram_account_id' => $account->id,
                'status' => TelegramLessonAbsenceRequest::STATUS_REQUESTED,
                'requested_at' => now(),
            ],
        );

        if (! $request->wasRecentlyCreated) {
            return 'Викладача вже повідомлено про вашу відсутність.';
        }

        $teacherAccount = $lesson->teacher?->user?->telegramAccount;

        if ($teacherAccount?->notifications_enabled) {
            $this->bot->sendMessage(
                $teacherAccount->chat_id,
                implode("\n", [
                    '<b>Учень повідомив про відсутність</b>',
                    '',
                    '<b>Учень:</b> '.$this->escape($student->full_name),
                    '<b>Заняття:</b> '.$this->escape($lesson->title),
                    '<b>Дата і час:</b> '.$lesson->start_date->format('d.m.Y H:i'),
                    '',
                    'Заняття не скасовано автоматично.',
                ]),
            );
        }

        return 'Дякуємо. Викладача повідомлено; заняття не скасовано автоматично.';
    }

    private function studentBelongsToLesson(PlannedLesson $lesson, int $studentId): bool
    {
        return (int) $lesson->student_id === $studentId
            || $lesson->group?->students->contains('id', $studentId) === true;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
