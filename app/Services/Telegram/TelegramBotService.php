<?php

namespace App\Services\Telegram;

use App\Enums\LessonStatus;
use App\Models\PlannedLesson;
use App\Models\TelegramAccount;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class TelegramBotService
{
    public function __construct(
        private readonly TelegramBotClient $client,
        private readonly TelegramLinkService $linkService,
        private readonly TelegramTeacherLessonService $teacherLessons,
    ) {}

    public function handle(array $update): void
    {
        if (is_array($update['callback_query'] ?? null)) {
            $this->handleCallbackQuery($update['callback_query']);

            return;
        }

        $message = $update['message'] ?? null;

        if (
            ! is_array($message)
            || ! isset($message['chat']['id'])
            || ($message['chat']['type'] ?? null) !== 'private'
            || ($message['from']['is_bot'] ?? false)
        ) {
            return;
        }

        $chatId = (string) $message['chat']['id'];
        $text = trim((string) ($message['text'] ?? ''));
        $telegramUser = is_array($message['from'] ?? null) ? $message['from'] : [];

        if (preg_match('/^\/start(?:@\w+)?(?:\s+(\S+))?$/u', $text, $matches)) {
            $this->handleStart($chatId, $telegramUser, $matches[1] ?? '');

            return;
        }

        $account = TelegramAccount::query()
            ->with(['user.student', 'user.teacher'])
            ->where('chat_id', $chatId)
            ->first();

        if (! $account || ! $this->hasSupportedProfile($account)) {
            $this->client->sendMessage(
                $chatId,
                'Спочатку підключіть Telegram у своєму кабінеті на сайті школи.',
            );

            return;
        }

        $account->update(['last_interaction_at' => now()]);

        if ($account->user->isTeacher() && $this->handlePendingReschedule($account, $text)) {
            return;
        }

        match ($text) {
            '/lessons', 'Мої заняття', 'Мій розклад' => $this->sendUpcomingLessons($account),
            '/subscription', 'Мій абонемент' => $account->user->isStudent()
                ? $this->sendSubscription($account)
                : $this->sendMenu($account),
            '/help', '/settings', 'Допомога' => $this->sendMenu($account),
            default => $this->sendMenu($account),
        };
    }

    private function handleStart(string $chatId, array $telegramUser, string $plainToken): void
    {
        if ($plainToken === '') {
            $this->client->sendMessage(
                $chatId,
                'Відкрийте свій кабінет на сайті та натисніть «Підключити Telegram».',
            );

            return;
        }

        $result = $this->linkService->connect($plainToken, $telegramUser, $chatId);

        $message = match ($result) {
            'connected' => 'Telegram успішно підключено до вашого кабінету.',
            'telegram_conflict' => 'Цей Telegram уже підключений до іншого кабінету.',
            'user_conflict' => 'До вашого кабінету вже підключений інший Telegram. Спочатку від’єднайте його на сайті.',
            default => 'Посилання недійсне або вже прострочене. Створіть нове у своєму кабінеті.',
        };

        if ($result === 'connected') {
            $account = TelegramAccount::query()
                ->with(['user.student', 'user.teacher'])
                ->where('chat_id', $chatId)
                ->first();

            if ($account) {
                $this->sendMenu($account, $message);
            }

            return;
        }

        $this->client->sendMessage($chatId, $message);
    }

    private function sendMenu(TelegramAccount $account, string $prefix = ''): void
    {
        $text = trim($prefix."\n\nОберіть потрібну дію:");
        $keyboard = $account->user?->isTeacher()
            ? [
                [['text' => 'Мій розклад']],
                [['text' => 'Допомога']],
            ]
            : [
                [
                    ['text' => 'Мої заняття'],
                    ['text' => 'Мій абонемент'],
                ],
                [['text' => 'Допомога']],
            ];

        $this->client->sendMessage($account->chat_id, $text, [
            'reply_markup' => [
                'keyboard' => $keyboard,
                'resize_keyboard' => true,
                'is_persistent' => true,
            ],
        ]);
    }

    private function sendUpcomingLessons(TelegramAccount $account): void
    {
        $lessons = $this->upcomingLessons($account);

        if ($lessons->isEmpty()) {
            $this->client->sendMessage(
                $account->chat_id,
                'Найближчих запланованих занять поки немає.',
            );

            return;
        }

        if ($account->user->isTeacher()) {
            $this->sendTeacherLessons($account, $lessons);

            return;
        }

        $lines = ['<b>Найближчі заняття</b>'];

        foreach ($lessons as $lesson) {
            $teacher = $lesson->teacher?->full_name ?: 'Викладач уточнюється';
            $lines[] = sprintf(
                "\n<b>%s о %s</b>\n%s\nВикладач: %s",
                $lesson->start_date->format('d.m.Y'),
                $lesson->start_date->format('H:i'),
                $this->escape($lesson->title),
                $this->escape($teacher),
            );
        }

        $this->client->sendMessage($account->chat_id, implode("\n", $lines));
    }

    private function sendTeacherLessons(TelegramAccount $account, $lessons): void
    {
        $this->client->sendMessage($account->chat_id, '<b>Ваш найближчий розклад</b>');

        foreach ($lessons as $lesson) {
            $details = [
                sprintf(
                    "<b>%s о %s</b>\n%s",
                    $lesson->start_date->format('d.m.Y'),
                    $lesson->start_date->format('H:i'),
                    $this->escape($lesson->title),
                ),
            ];

            if ($lesson->group) {
                $details[] = 'Група: '.$this->escape($lesson->group->name);
            } elseif ($lesson->student) {
                $details[] = 'Учень: '.$this->escape($lesson->student->full_name);
            } else {
                $details[] = 'Пробне заняття без прив’язаного учня';
            }

            $this->client->sendMessage(
                $account->chat_id,
                implode("\n", $details),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [
                            [
                                [
                                    'text' => 'Проведено',
                                    'callback_data' => "lesson:complete:{$lesson->id}",
                                ],
                                [
                                    'text' => 'Перенести',
                                    'callback_data' => "lesson:reschedule:{$lesson->id}",
                                ],
                            ],
                            [
                                [
                                    'text' => 'Скасувати',
                                    'callback_data' => "lesson:cancel:{$lesson->id}",
                                ],
                            ],
                        ],
                    ],
                ],
            );
        }
    }

    private function handleCallbackQuery(array $callback): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $messageId = (int) ($callback['message']['message_id'] ?? 0);
        $telegramUserId = (string) ($callback['from']['id'] ?? '');

        $account = TelegramAccount::query()
            ->with(['user.teacher'])
            ->where('chat_id', $chatId)
            ->where('telegram_user_id', $telegramUserId)
            ->first();

        if (! $account || ! $account->user?->isTeacher() || ! $account->user->teacher) {
            $this->client->answerCallbackQuery(
                $callbackId,
                'Дія доступна лише підключеному викладачеві.',
            );

            return;
        }

        if (! preg_match('/^lesson:(complete|complete_all|reschedule|cancel|cancel_confirm):(\d+)$/', $data, $matches)) {
            $this->client->answerCallbackQuery($callbackId, 'Невідома дія.');

            return;
        }

        $action = $matches[1];
        $lessonId = (int) $matches[2];
        $lesson = PlannedLesson::query()
            ->whereKey($lessonId)
            ->where('teacher_id', $account->user->teacher->id)
            ->where('status', LessonStatus::Planned->value)
            ->first();

        if (! $lesson) {
            $this->client->answerCallbackQuery(
                $callbackId,
                'Заняття вже змінене або недоступне.',
            );
            $this->removeKeyboard($chatId, $messageId);

            return;
        }

        $account->update(['last_interaction_at' => now()]);

        try {
            match ($action) {
                'complete' => $this->requestCompletionConfirmation(
                    $account,
                    $lesson,
                    $callbackId,
                    $messageId,
                ),
                'complete_all' => $this->completeLesson(
                    $account,
                    $lessonId,
                    $callbackId,
                    $messageId,
                ),
                'reschedule' => $this->requestReschedule(
                    $account,
                    $lessonId,
                    $callbackId,
                ),
                'cancel' => $this->requestCancellationConfirmation(
                    $account,
                    $lesson,
                    $callbackId,
                ),
                'cancel_confirm' => $this->cancelLesson(
                    $account,
                    $lessonId,
                    $callbackId,
                    $messageId,
                ),
            };
        } catch (Throwable $exception) {
            $this->client->answerCallbackQuery($callbackId, 'Не вдалося виконати дію.');
            $this->client->sendMessage(
                $chatId,
                'Не вдалося змінити заняття: '.$this->escape($exception->getMessage()),
            );
        }
    }

    private function requestCompletionConfirmation(
        TelegramAccount $account,
        PlannedLesson $lesson,
        string $callbackId,
        int $messageId,
    ): void {
        if (! $lesson->group_id) {
            $this->completeLesson($account, $lesson->id, $callbackId, $messageId);

            return;
        }

        $this->client->answerCallbackQuery($callbackId);
        $this->client->sendMessage(
            $account->chat_id,
            "Підтвердіть: на занятті «{$this->escape($lesson->title)}» були присутні всі учні?\n\nЯкщо хтось був відсутній, відмітьте відвідування в календарі.",
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [[
                            'text' => 'Так, усі присутні',
                            'callback_data' => "lesson:complete_all:{$lesson->id}",
                        ]],
                    ],
                ],
            ],
        );
    }

    private function completeLesson(
        TelegramAccount $account,
        int $lessonId,
        string $callbackId,
        int $messageId,
    ): void {
        $lesson = $this->teacherLessons->complete(
            $lessonId,
            $account->user->teacher,
            $account->user_id,
        );

        $this->client->answerCallbackQuery($callbackId, 'Заняття відмічено проведеним.');
        $this->removeKeyboard($account->chat_id, $messageId);
        $this->client->sendMessage(
            $account->chat_id,
            sprintf(
                'Заняття %s о %s відмічено як проведене.',
                $lesson->start_date->format('d.m.Y'),
                $lesson->start_date->format('H:i'),
            ),
        );
    }

    private function requestCancellationConfirmation(
        TelegramAccount $account,
        PlannedLesson $lesson,
        string $callbackId,
    ): void {
        $this->client->answerCallbackQuery($callbackId);
        $this->client->sendMessage(
            $account->chat_id,
            sprintf(
                "Скасувати заняття %s о %s?\nЦе також очистить пов’язані записи відвідування.",
                $lesson->start_date->format('d.m.Y'),
                $lesson->start_date->format('H:i'),
            ),
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [[
                            'text' => 'Так, скасувати',
                            'callback_data' => "lesson:cancel_confirm:{$lesson->id}",
                        ]],
                    ],
                ],
            ],
        );
    }

    private function cancelLesson(
        TelegramAccount $account,
        int $lessonId,
        string $callbackId,
        int $messageId,
    ): void {
        $lesson = $this->teacherLessons->cancel(
            $lessonId,
            $account->user->teacher,
            $account->user_id,
        );

        $this->client->answerCallbackQuery($callbackId, 'Заняття скасовано.');
        $this->removeKeyboard($account->chat_id, $messageId);
        $this->client->sendMessage(
            $account->chat_id,
            sprintf(
                'Заняття %s о %s скасовано.',
                $lesson->start_date->format('d.m.Y'),
                $lesson->start_date->format('H:i'),
            ),
        );
    }

    private function requestReschedule(
        TelegramAccount $account,
        int $lessonId,
        string $callbackId,
    ): void {
        Cache::put($this->rescheduleCacheKey($account), $lessonId, now()->addMinutes(15));

        $this->client->answerCallbackQuery($callbackId);
        $this->client->sendMessage(
            $account->chat_id,
            "Вкажіть нові дату і час одним повідомленням.\nФормат: <b>31.07.2026 18:30</b>\n\nДля скасування введення надішліть /cancel.",
            [
                'reply_markup' => [
                    'force_reply' => true,
                    'selective' => true,
                    'input_field_placeholder' => '31.07.2026 18:30',
                ],
            ],
        );
    }

    private function handlePendingReschedule(TelegramAccount $account, string $text): bool
    {
        $cacheKey = $this->rescheduleCacheKey($account);
        $lessonId = Cache::get($cacheKey);

        if (! is_numeric($lessonId)) {
            return false;
        }

        if ($text === '/cancel') {
            Cache::forget($cacheKey);
            $this->client->sendMessage($account->chat_id, 'Перенесення скасовано.');

            return true;
        }

        $newStart = $this->parseRescheduleDateTime($text);

        if (! $newStart) {
            $this->client->sendMessage(
                $account->chat_id,
                "Не вдалося прочитати дату і час. Надішліть їх у форматі:\n<b>31.07.2026 18:30</b>",
            );

            return true;
        }

        try {
            $newLesson = $this->teacherLessons->reschedule(
                (int) $lessonId,
                $account->user->teacher,
                $account->user_id,
                $newStart,
            );
        } catch (Throwable $exception) {
            $this->client->sendMessage(
                $account->chat_id,
                'Не вдалося перенести заняття: '.$this->escape($exception->getMessage()),
            );

            return true;
        }

        Cache::forget($cacheKey);
        $this->client->sendMessage(
            $account->chat_id,
            sprintf(
                'Заняття перенесено на %s о %s.',
                $newLesson->start_date->format('d.m.Y'),
                $newLesson->start_date->format('H:i'),
            ),
        );

        return true;
    }

    private function sendSubscription(TelegramAccount $account): void
    {
        $subscription = $account->user->student
            ->subscriptions()
            ->with('subscriptionTemplate')
            ->where('status', 'active')
            ->latest('start_date')
            ->first();

        if (! $subscription) {
            $this->client->sendMessage(
                $account->chat_id,
                'Активного абонемента зараз немає. Перевірити оплату можна в кабінеті учня.',
            );

            return;
        }

        $title = $subscription->subscriptionTemplate?->title
            ?: $subscription->subscription_title
            ?: 'Абонемент';

        $this->client->sendMessage(
            $account->chat_id,
            sprintf(
                "<b>%s</b>\nСтатус: активний\nПеріод: %s — %s",
                $this->escape($title),
                $subscription->start_date->format('d.m.Y'),
                $subscription->end_date->format('d.m.Y'),
            ),
        );
    }

    private function upcomingLessons(TelegramAccount $account)
    {
        $query = PlannedLesson::query()
            ->with(['teacher', 'student', 'group'])
            ->where('status', LessonStatus::Planned->value);

        if ($account->user->isTeacher()) {
            $query->where('teacher_id', $account->user->teacher->id);

            $recentUnresolved = (clone $query)
                ->whereBetween('start_date', [now()->subDays(7), now()])
                ->orderByDesc('start_date')
                ->limit(5)
                ->get();
            $upcoming = (clone $query)
                ->where('start_date', '>', now())
                ->orderBy('start_date')
                ->limit(5)
                ->get();

            return $recentUnresolved->concat($upcoming);
        }

        $student = $account->user->student;

        return $query
            ->where('start_date', '>=', now())
            ->where(function ($query) use ($student) {
                $query->where('student_id', $student->id);

                if ($student->group_id) {
                    $query->orWhere('group_id', $student->group_id);
                }
            })
            ->orderBy('start_date')
            ->limit(5)
            ->get();
    }

    private function parseRescheduleDateTime(string $value): ?CarbonImmutable
    {
        foreach (['!d.m.Y H:i', '!Y-m-d H:i'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, trim($value));
                $errors = CarbonImmutable::getLastErrors();

                if (
                    $date
                    && (
                        $errors === false
                        || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)
                    )
                ) {
                    return $date;
                }
            } catch (Throwable) {
                // Try the next supported format.
            }
        }

        return null;
    }

    private function rescheduleCacheKey(TelegramAccount $account): string
    {
        return "telegram:teacher:{$account->id}:pending-reschedule";
    }

    private function removeKeyboard(string $chatId, int $messageId): void
    {
        if ($messageId > 0) {
            $this->client->removeInlineKeyboard($chatId, $messageId);
        }
    }

    private function hasSupportedProfile(TelegramAccount $account): bool
    {
        return ($account->user?->isStudent() && $account->user->student)
            || ($account->user?->isTeacher() && $account->user->teacher);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
