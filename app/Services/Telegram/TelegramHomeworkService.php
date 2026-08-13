<?php

namespace App\Services\Telegram;

use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\TelegramAccount;
use App\Models\TelegramHomeworkAssignment;
use App\Models\TelegramHomeworkSubmission;
use Illuminate\Support\Facades\Cache;

class TelegramHomeworkService
{
    public function __construct(private readonly TelegramBotClient $bot) {}

    public function beginAssignment(
        TelegramAccount $account,
        int $lessonId,
        string $callbackId,
    ): void {
        $lesson = PlannedLesson::query()
            ->whereKey($lessonId)
            ->where('teacher_id', $account->user?->teacher?->id)
            ->first();

        if (! $lesson) {
            $this->bot->answerCallbackQuery($callbackId, 'Заняття недоступне.');

            return;
        }

        Cache::put($this->teacherCacheKey($account), $lesson->id, now()->addMinutes(15));
        $this->bot->answerCallbackQuery($callbackId);
        $this->bot->sendMessage(
            $account->chat_id,
            "Надішліть домашнє завдання одним повідомленням: текст, документ або фото.\n\nДля скасування надішліть /cancel.",
            ['reply_markup' => ['force_reply' => true, 'selective' => true]],
        );
    }

    public function beginSubmission(
        TelegramAccount $account,
        int $assignmentId,
        string $callbackId,
    ): void {
        $assignment = TelegramHomeworkAssignment::query()
            ->with('plannedLesson.group.students')
            ->find($assignmentId);
        $student = $account->user?->student;

        if (! $assignment || ! $student
            || ! $this->studentBelongsToLesson($assignment->plannedLesson, $student->id)) {
            $this->bot->answerCallbackQuery($callbackId, 'Домашнє завдання недоступне.');

            return;
        }

        Cache::put($this->studentCacheKey($account), $assignment->id, now()->addMinutes(15));
        $this->bot->answerCallbackQuery($callbackId);
        $this->bot->sendMessage(
            $account->chat_id,
            "Надішліть відповідь одним повідомленням: текст, документ або фото.\n\nДля скасування надішліть /cancel.",
            ['reply_markup' => ['force_reply' => true, 'selective' => true]],
        );
    }

    public function handlePendingMessage(TelegramAccount $account, array $message): bool
    {
        if ($account->user?->isTeacher() && Cache::has($this->teacherCacheKey($account))) {
            return $this->storeAssignment($account, $message);
        }

        if ($account->user?->isStudent() && Cache::has($this->studentCacheKey($account))) {
            return $this->storeSubmission($account, $message);
        }

        return false;
    }

    public function sendAssignments(TelegramAccount $account): void
    {
        $student = $account->user?->student;

        if (! $student) {
            return;
        }

        $assignments = TelegramHomeworkAssignment::query()
            ->with(['plannedLesson', 'submissions' => fn ($query) => $query
                ->where('student_id', $student->id)])
            ->whereHas('plannedLesson', function ($query) use ($student) {
                $query->where('student_id', $student->id);

                if ($student->group_id) {
                    $query->orWhere('group_id', $student->group_id);
                }
            })
            ->latest('assigned_at')
            ->limit(10)
            ->get();

        if ($assignments->isEmpty()) {
            $this->bot->sendMessage($account->chat_id, 'Домашніх завдань поки немає.');

            return;
        }

        foreach ($assignments as $assignment) {
            $submission = $assignment->submissions->first();
            $status = match ($submission?->status) {
                TelegramHomeworkSubmission::STATUS_REVIEWED => 'перевірено',
                TelegramHomeworkSubmission::STATUS_SUBMITTED => 'надіслано на перевірку',
                default => 'очікує відповіді',
            };
            $this->sendAssignmentToStudent($assignment, $account, $status);
        }
    }

    public function reviewSubmission(
        TelegramAccount $account,
        int $submissionId,
        string $callbackId,
    ): void {
        $submission = TelegramHomeworkSubmission::query()
            ->with(['assignment.plannedLesson', 'student.user.telegramAccount'])
            ->whereKey($submissionId)
            ->whereHas('assignment', fn ($query) => $query
                ->where('teacher_id', $account->user?->teacher?->id))
            ->first();

        if (! $submission) {
            $this->bot->answerCallbackQuery($callbackId, 'Роботу не знайдено.');

            return;
        }

        if ($submission->status !== TelegramHomeworkSubmission::STATUS_REVIEWED) {
            $submission->update([
                'status' => TelegramHomeworkSubmission::STATUS_REVIEWED,
                'reviewed_at' => now(),
            ]);
        }

        $this->bot->answerCallbackQuery($callbackId, 'Роботу позначено перевіреною.');
        $studentAccount = $submission->student?->user?->telegramAccount;

        if ($studentAccount?->notifications_enabled
            && $studentAccount->homework_notifications_enabled) {
            $this->bot->sendMessage(
                $studentAccount->chat_id,
                '<b>Домашню роботу перевірено</b>'
                    ."\n".$this->escape($submission->assignment->plannedLesson->title),
            );
        }
    }

    private function storeAssignment(TelegramAccount $account, array $message): bool
    {
        $cacheKey = $this->teacherCacheKey($account);

        if (trim((string) ($message['text'] ?? '')) === '/cancel') {
            Cache::forget($cacheKey);
            $this->bot->sendMessage($account->chat_id, 'Створення домашнього завдання скасовано.');

            return true;
        }

        $lessonId = Cache::get($cacheKey);
        $lesson = PlannedLesson::query()
            ->with(['student.user.telegramAccount', 'group.students.user.telegramAccount'])
            ->whereKey($lessonId)
            ->where('teacher_id', $account->user?->teacher?->id)
            ->first();
        $content = $this->messageContent($message);

        if (! $lesson || ($content['text'] === null && $content['file_id'] === null)) {
            $this->bot->sendMessage(
                $account->chat_id,
                'Надішліть текст, документ або фото одним повідомленням.',
            );

            return true;
        }

        $assignment = TelegramHomeworkAssignment::query()->updateOrCreate(
            ['planned_lesson_id' => $lesson->id],
            [
                'teacher_id' => $account->user->teacher->id,
                'text' => $content['text'],
                'telegram_file_id' => $content['file_id'],
                'telegram_file_type' => $content['file_type'],
                'file_name' => $content['file_name'],
                'assigned_at' => now(),
            ],
        );

        if (! $assignment->wasRecentlyCreated) {
            $assignment->submissions()->delete();
        }

        Cache::forget($cacheKey);

        foreach ($this->studentAccountsFor($lesson) as $studentAccount) {
            $this->sendAssignmentToStudent($assignment, $studentAccount);
        }

        $this->bot->sendMessage($account->chat_id, 'Домашнє завдання збережено та надіслано учням.');

        return true;
    }

    private function storeSubmission(TelegramAccount $account, array $message): bool
    {
        $cacheKey = $this->studentCacheKey($account);

        if (trim((string) ($message['text'] ?? '')) === '/cancel') {
            Cache::forget($cacheKey);
            $this->bot->sendMessage($account->chat_id, 'Надсилання домашньої роботи скасовано.');

            return true;
        }

        $assignment = TelegramHomeworkAssignment::query()
            ->with(['plannedLesson.teacher.user.telegramAccount', 'plannedLesson.group.students'])
            ->find(Cache::get($cacheKey));
        $student = $account->user?->student;
        $content = $this->messageContent($message);

        if (! $assignment || ! $student
            || ! $this->studentBelongsToLesson($assignment->plannedLesson, $student->id)
            || ($content['text'] === null && $content['file_id'] === null)) {
            $this->bot->sendMessage(
                $account->chat_id,
                'Надішліть текст, документ або фото одним повідомленням.',
            );

            return true;
        }

        $submission = TelegramHomeworkSubmission::query()->updateOrCreate(
            [
                'telegram_homework_assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'text' => $content['text'],
                'telegram_file_id' => $content['file_id'],
                'telegram_file_type' => $content['file_type'],
                'file_name' => $content['file_name'],
                'status' => TelegramHomeworkSubmission::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'reviewed_at' => null,
            ],
        );

        Cache::forget($cacheKey);
        $teacherAccount = $assignment->plannedLesson->teacher?->user?->telegramAccount;

        if ($teacherAccount?->notifications_enabled
            && $teacherAccount->homework_notifications_enabled) {
            $this->bot->sendMessage(
                $teacherAccount->chat_id,
                implode("\n", array_filter([
                    '<b>Нова домашня робота</b>',
                    '',
                    '<b>Учень:</b> '.$this->escape($student->full_name),
                    '<b>Заняття:</b> '.$this->escape($assignment->plannedLesson->title),
                    $content['text'] ? '<b>Відповідь:</b> '.$this->escape($content['text']) : null,
                ])),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => 'Позначити перевіреною',
                                'callback_data' => "homework:review:{$submission->id}",
                            ],
                        ]],
                    ],
                ],
            );
            $this->sendStoredFile($teacherAccount->chat_id, $submission);
        }

        $this->bot->sendMessage($account->chat_id, 'Домашню роботу надіслано викладачеві.');

        return true;
    }

    private function sendAssignmentToStudent(
        TelegramHomeworkAssignment $assignment,
        TelegramAccount $account,
        string $status = 'очікує відповіді',
    ): void {
        $this->bot->sendMessage(
            $account->chat_id,
            implode("\n", array_filter([
                '<b>Домашнє завдання</b>',
                '',
                '<b>Заняття:</b> '.$this->escape($assignment->plannedLesson->title),
                $assignment->text ? '<b>Завдання:</b> '.$this->escape($assignment->text) : null,
                '<b>Статус:</b> '.$this->escape($status),
            ])),
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => 'Надіслати відповідь',
                            'callback_data' => "homework:submit:{$assignment->id}",
                        ],
                    ]],
                ],
            ],
        );
        $this->sendStoredFile($account->chat_id, $assignment);
    }

    private function studentAccountsFor(PlannedLesson $lesson)
    {
        $students = collect();

        if ($lesson->student) {
            $students->push($lesson->student);
        }

        if ($lesson->group) {
            $students = $students->merge($lesson->group->students);
        }

        return $students
            ->unique('id')
            ->map(fn (Student $student) => $student->user?->telegramAccount)
            ->filter(fn (?TelegramAccount $account) => $account?->notifications_enabled === true
                && $account->homework_notifications_enabled === true)
            ->values();
    }

    private function sendStoredFile(string $chatId, object $record): void
    {
        if (! $record->telegram_file_id) {
            return;
        }

        if ($record->telegram_file_type === 'photo') {
            $this->bot->sendPhoto($chatId, $record->telegram_file_id);

            return;
        }

        $this->bot->sendDocument($chatId, $record->telegram_file_id);
    }

    private function messageContent(array $message): array
    {
        $text = trim((string) ($message['text'] ?? $message['caption'] ?? '')) ?: null;
        $document = is_array($message['document'] ?? null) ? $message['document'] : null;
        $photos = is_array($message['photo'] ?? null) ? $message['photo'] : [];
        $photo = $photos === [] ? null : end($photos);

        if ($document) {
            return [
                'text' => $text,
                'file_id' => (string) ($document['file_id'] ?? '') ?: null,
                'file_type' => 'document',
                'file_name' => (string) ($document['file_name'] ?? '') ?: null,
            ];
        }

        if (is_array($photo)) {
            return [
                'text' => $text,
                'file_id' => (string) ($photo['file_id'] ?? '') ?: null,
                'file_type' => 'photo',
                'file_name' => null,
            ];
        }

        return [
            'text' => $text,
            'file_id' => null,
            'file_type' => null,
            'file_name' => null,
        ];
    }

    private function studentBelongsToLesson(?PlannedLesson $lesson, int $studentId): bool
    {
        return $lesson && (
            (int) $lesson->student_id === $studentId
            || $lesson->group?->students->contains('id', $studentId) === true
        );
    }

    private function teacherCacheKey(TelegramAccount $account): string
    {
        return "telegram:homework:teacher:{$account->id}";
    }

    private function studentCacheKey(TelegramAccount $account): string
    {
        return "telegram:homework:student:{$account->id}";
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
