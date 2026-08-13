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
        private readonly TelegramLessonAbsenceService $absenceRequests,
        private readonly TelegramHomeworkService $homework,
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

        if ($account->user->hasActiveTeacherProfile() && $this->handlePendingReschedule($account, $text)) {
            return;
        }

        if ($this->homework->handlePendingMessage($account, $message)) {
            return;
        }

        match ($text) {
            '/lessons', 'Мої заняття', 'Мій розклад' => $this->sendUpcomingLessons($account),
            '/subscription', 'Мій абонемент' => $account->user->isStudent()
                ? $this->sendSubscription($account)
                : $this->sendMenu($account),
            '/payments', 'Мої платежі' => $account->user->isStudent()
                ? $this->sendPayments($account)
                : $this->sendMenu($account),
            '/homework', 'Домашні завдання' => $account->user->isStudent()
                ? $this->homework->sendAssignments($account)
                : $this->sendMenu($account),
            '/settings', 'Налаштування' => $this->sendSettings($account),
            '/help', 'Допомога' => $this->sendHelp($account),
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
        $keyboard = $account->user?->hasActiveTeacherProfile()
            ? [
                [['text' => 'Мій розклад']],
                [
                    ['text' => 'Налаштування'],
                    ['text' => 'Допомога'],
                ],
            ]
            : [
                [
                    ['text' => 'Мої заняття'],
                    ['text' => 'Мій абонемент'],
                ],
                [
                    ['text' => 'Мої платежі'],
                    ['text' => 'Домашні завдання'],
                ],
                [
                    ['text' => 'Налаштування'],
                    ['text' => 'Допомога'],
                ],
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

        if ($account->user->hasActiveTeacherProfile()) {
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

        $keyboard = [];

        foreach ($lessons as $lesson) {
            $row = [];

            if ($lesson->effective_meeting_url) {
                $row[] = [
                    'text' => 'Приєднатися '.$lesson->start_date->format('d.m H:i'),
                    'url' => $lesson->effective_meeting_url,
                ];
            }

            $row[] = [
                'text' => 'Не зможу бути '.$lesson->start_date->format('d.m H:i'),
                'callback_data' => "student:absence:{$lesson->id}",
            ];
            $keyboard[] = $row;
        }

        $this->client->sendMessage($account->chat_id, implode("\n", $lines), [
            'reply_markup' => ['inline_keyboard' => $keyboard],
        ]);
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
                        'inline_keyboard' => array_values(array_filter([
                            $lesson->effective_meeting_url ? [[
                                'text' => 'Приєднатися',
                                'url' => $lesson->effective_meeting_url,
                            ]] : null,
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
                                [
                                    'text' => 'Домашнє завдання',
                                    'callback_data' => "homework:create:{$lesson->id}",
                                ],
                            ],
                        ])),
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
            ->with(['user.student', 'user.teacher'])
            ->where('chat_id', $chatId)
            ->where('telegram_user_id', $telegramUserId)
            ->first();

        if (! $account || ! $this->hasSupportedProfile($account)) {
            $this->client->answerCallbackQuery(
                $callbackId,
                'Спочатку підключіть Telegram у своєму кабінеті.',
            );

            return;
        }

        $account->update(['last_interaction_at' => now()]);

        if (preg_match('/^student:absence:(\d+)$/', $data, $matches)) {
            $message = $account->user->isStudent()
                ? $this->absenceRequests->request($account, (int) $matches[1])
                : 'Ця дія доступна лише учням.';
            $this->client->answerCallbackQuery($callbackId, $message);
            $this->client->sendMessage($account->chat_id, $message);

            return;
        }

        if (preg_match('/^settings:(lesson|payment|homework)$/', $data, $matches)) {
            $this->toggleSetting($account, $matches[1], $callbackId);

            return;
        }

        if (preg_match('/^settings:lead:(30|120|1440)$/', $data, $matches)) {
            $account->update(['lesson_reminder_minutes' => (int) $matches[1]]);
            $this->client->answerCallbackQuery($callbackId, 'Час нагадування оновлено.');
            $this->sendSettings($account->fresh());

            return;
        }

        if (preg_match('/^homework:(create|submit|review):(\d+)$/', $data, $matches)) {
            match ($matches[1]) {
                'create' => $this->homework->beginAssignment(
                    $account,
                    (int) $matches[2],
                    $callbackId,
                ),
                'submit' => $this->homework->beginSubmission(
                    $account,
                    (int) $matches[2],
                    $callbackId,
                ),
                'review' => $this->homework->reviewSubmission(
                    $account,
                    (int) $matches[2],
                    $callbackId,
                ),
            };

            return;
        }

        if (! $account->user?->hasActiveTeacherProfile()) {
            $this->client->answerCallbackQuery($callbackId, 'Дія доступна лише викладачеві.');

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

    private function sendPayments(TelegramAccount $account): void
    {
        $student = $account->user->student;
        $currentMonth = now()->startOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth();
        $subscriptions = $student->subscriptions()
            ->where('type', 'subscription')
            ->whereIn('status', ['active', 'expired'])
            ->whereBetween('start_date', [
                $currentMonth->toDateString(),
                $nextMonth->copy()->endOfMonth()->toDateString(),
            ])
            ->get();
        $lastPayment = $student->payments()
            ->where('status', 'paid')
            ->latest('paid_at')
            ->first();

        $paidCurrent = $subscriptions->contains(
            fn ($subscription) => $subscription->start_date->isSameMonth($currentMonth),
        );
        $paidNext = $subscriptions->contains(
            fn ($subscription) => $subscription->start_date->isSameMonth($nextMonth),
        );
        $lines = [
            '<b>Статус оплат</b>',
            '',
            '<b>'.$currentMonth->copy()->locale('uk')->translatedFormat('F Y').':</b> '
                .($paidCurrent ? 'оплачено' : 'не оплачено'),
            '<b>'.$nextMonth->copy()->locale('uk')->translatedFormat('F Y').':</b> '
                .($paidNext ? 'оплачено' : 'не оплачено'),
        ];

        if ($lastPayment) {
            $lines[] = '';
            $lines[] = '<b>Остання оплата:</b> '
                .number_format((float) $lastPayment->amount, 2, '.', ' ').' '
                .$this->escape($lastPayment->currency)
                .' · '.($lastPayment->paid_at?->format('d.m.Y') ?? '—');
        }

        $this->client->sendMessage($account->chat_id, implode("\n", $lines), [
            'reply_markup' => [
                'inline_keyboard' => [[
                    [
                        'text' => $paidCurrent && $paidNext ? 'Відкрити платежі' : 'Перейти до оплати',
                        'url' => route('student.payments.index'),
                    ],
                ]],
            ],
        ]);
    }

    private function sendSettings(TelegramAccount $account): void
    {
        $leadLabel = match ((int) $account->lesson_reminder_minutes) {
            1440 => 'за 24 години',
            120 => 'за 2 години',
            30 => 'за 30 хвилин',
            default => 'за 60 хвилин',
        };
        $masterWarning = $account->notifications_enabled
            ? ''
            : "\n\nЗагальні сповіщення вимкнено у вебкабінеті.";

        $this->client->sendMessage(
            $account->chat_id,
            '<b>Налаштування Telegram</b>'
                ."\n\nНагадування про заняття: ".$this->enabledLabel($account->lesson_reminders_enabled)
                ."\nЧас нагадування: ".$leadLabel
                ."\nОплати: ".$this->enabledLabel($account->payment_notifications_enabled)
                ."\nДомашні завдання: ".$this->enabledLabel($account->homework_notifications_enabled)
                .$masterWarning,
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [[
                            'text' => 'Заняття: '.$this->toggleLabel($account->lesson_reminders_enabled),
                            'callback_data' => 'settings:lesson',
                        ]],
                        [
                            [
                                'text' => '30 хв',
                                'callback_data' => 'settings:lead:30',
                            ],
                            [
                                'text' => '2 год',
                                'callback_data' => 'settings:lead:120',
                            ],
                            [
                                'text' => '24 год',
                                'callback_data' => 'settings:lead:1440',
                            ],
                        ],
                        [[
                            'text' => 'Оплати: '.$this->toggleLabel($account->payment_notifications_enabled),
                            'callback_data' => 'settings:payment',
                        ]],
                        [[
                            'text' => 'Домашнє: '.$this->toggleLabel($account->homework_notifications_enabled),
                            'callback_data' => 'settings:homework',
                        ]],
                    ],
                ],
            ],
        );
    }

    private function toggleSetting(
        TelegramAccount $account,
        string $setting,
        string $callbackId,
    ): void {
        $column = match ($setting) {
            'lesson' => 'lesson_reminders_enabled',
            'payment' => 'payment_notifications_enabled',
            'homework' => 'homework_notifications_enabled',
        };

        $account->update([$column => ! (bool) $account->{$column}]);
        $this->client->answerCallbackQuery($callbackId, 'Налаштування оновлено.');
        $this->sendSettings($account->fresh());
    }

    private function sendHelp(TelegramAccount $account): void
    {
        $commands = $account->user?->hasActiveTeacherProfile()
            ? [
                '/lessons — найближчі заняття та керування ними',
                '/settings — налаштування сповіщень',
            ]
            : [
                '/lessons — найближчі заняття',
                '/subscription — активний абонемент',
                '/payments — статус оплат і кнопка оплати',
                '/homework — домашні завдання',
                '/settings — налаштування сповіщень',
            ];

        $this->client->sendMessage(
            $account->chat_id,
            "<b>Допомога</b>\n\n".implode("\n", $commands),
        );
    }

    private function enabledLabel(bool $enabled): string
    {
        return $enabled ? 'увімкнено' : 'вимкнено';
    }

    private function toggleLabel(bool $enabled): string
    {
        return $enabled ? 'вимкнути' : 'увімкнути';
    }

    private function upcomingLessons(TelegramAccount $account)
    {
        $query = PlannedLesson::query()
            ->with(['teacher', 'student', 'group'])
            ->where('status', LessonStatus::Planned->value);

        if ($account->user->hasActiveTeacherProfile()) {
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
            || $account->user?->hasActiveTeacherProfile();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
