<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\Teacher;
use App\Models\TelegramAccount;
use App\Models\TelegramHomeworkAssignment;
use App\Models\TelegramHomeworkSubmission;
use App\Models\TelegramPaymentConfirmation;
use App\Models\User;
use App\Services\Telegram\TelegramPaymentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramBotEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'app.url' => 'https://school.example',
            'services.telegram.bot_token' => 'test-bot-token',
            'services.telegram.webhook_secret' => 'test-webhook-secret',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }

    public function test_paid_payment_sends_one_confirmation_with_payment_link(): void
    {
        [$user, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user);
        $payment = Payment::query()->create([
            'student_id' => $student->id,
            'amount' => 2400,
            'currency' => 'UAH',
            'status' => 'paid',
            'type' => 'subscription',
            'paid_at' => now(),
            'payload' => ['subscription_month' => now()->format('Y-m')],
        ]);

        $service = app(TelegramPaymentConfirmationService::class);

        $this->assertSame('sent', $service->sendForPayment($payment));
        $this->assertSame('skipped', $service->sendForPayment($payment));

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => ($request->data()['chat_id'] ?? null) === $account->chat_id
            && str_contains($request->data()['text'] ?? '', 'Оплату успішно отримано')
            && str_contains($request->data()['text'] ?? '', '2 400.00 UAH')
            && ($request->data()['reply_markup']['inline_keyboard'][0][0]['url'] ?? null)
                === route('student.payments.index'));
        $this->assertDatabaseHas('telegram_payment_confirmations', [
            'payment_id' => $payment->id,
            'status' => TelegramPaymentConfirmation::STATUS_SENT,
            'attempts' => 1,
        ]);
    }

    public function test_student_can_view_payment_status_and_open_payment_page(): void
    {
        [$user, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user);
        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);
        Payment::query()->create([
            'student_id' => $student->id,
            'amount' => 1800,
            'currency' => 'UAH',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->telegramWebhook($this->messagePayload('/payments', $account))->assertOk();

        Http::assertSent(fn ($request) => ($request->data()['chat_id'] ?? null) === $account->chat_id
            && str_contains($request->data()['text'] ?? '', 'Статус оплат')
            && str_contains($request->data()['text'] ?? '', 'оплачено')
            && str_contains($request->data()['text'] ?? '', '1 800.00 UAH')
            && ($request->data()['reply_markup']['inline_keyboard'][0][0]['url'] ?? null)
                === route('student.payments.index'));
    }

    public function test_student_absence_request_notifies_teacher_without_cancelling_lesson(): void
    {
        [$studentUser, $student] = $this->createStudentUser();
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $studentAccount = $this->createTelegramAccount($studentUser, 'student');
        $teacherAccount = $this->createTelegramAccount($teacherUser, 'teacher');
        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay()->addHour(),
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->callbackPayload(
            "student:absence:{$lesson->id}",
            $studentAccount,
        ))->assertOk();

        $this->assertDatabaseHas('telegram_lesson_absence_requests', [
            'planned_lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'status' => 'requested',
        ]);
        $this->assertSame('planned', $lesson->fresh()->status->value);
        Http::assertSent(fn ($request) => ($request->data()['chat_id'] ?? null) === $teacherAccount->chat_id
            && str_contains($request->data()['text'] ?? '', 'Учень повідомив про відсутність')
            && str_contains($request->data()['text'] ?? '', $student->full_name));
    }

    public function test_custom_reminder_time_and_meeting_link_are_used(): void
    {
        [$user, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user);
        $account->update(['lesson_reminder_minutes' => 1440]);
        $lesson = PlannedLesson::factory()->individual()->create([
            'student_id' => $student->id,
            'start_date' => now()->addHours(23),
            'end_date' => now()->addHours(24),
            'meeting_url' => 'https://meet.example/english-room',
            'status' => 'planned',
        ]);

        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 1; sent: 1; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertSent(fn ($request) => ($request->data()['chat_id'] ?? null) === $account->chat_id
            && ($request->data()['reply_markup']['inline_keyboard'][0][0]['url'] ?? null)
                === $lesson->meeting_url
            && ($request->data()['reply_markup']['inline_keyboard'][0][1]['callback_data'] ?? null)
                === "student:absence:{$lesson->id}");
    }

    public function test_teacher_default_zoom_link_is_used_when_lesson_has_no_override(): void
    {
        [$studentUser, $student] = $this->createStudentUser();
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $teacher->update(['meeting_url' => 'https://zoom.us/j/teacher-room']);
        $account = $this->createTelegramAccount($studentUser);
        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(50),
            'end_date' => now()->addMinutes(105),
            'meeting_url' => null,
            'status' => 'planned',
        ]);

        $this->artisan('telegram:lessons:remind')->assertSuccessful();

        Http::assertSent(fn ($request) => ($request->data()['chat_id'] ?? null) === $account->chat_id
            && ($request->data()['reply_markup']['inline_keyboard'][0][0]['url'] ?? null)
                === 'https://zoom.us/j/teacher-room');
    }

    public function test_lesson_zoom_link_overrides_teacher_default_link(): void
    {
        [$studentUser, $student] = $this->createStudentUser();
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $teacher->update(['meeting_url' => 'https://zoom.us/j/teacher-room']);
        $account = $this->createTelegramAccount($studentUser);
        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(50),
            'end_date' => now()->addMinutes(105),
            'meeting_url' => 'https://zoom.us/j/special-room',
            'status' => 'planned',
        ]);

        $this->artisan('telegram:lessons:remind')->assertSuccessful();

        Http::assertSent(fn ($request) => ($request->data()['chat_id'] ?? null) === $account->chat_id
            && ($request->data()['reply_markup']['inline_keyboard'][0][0]['url'] ?? null)
                === 'https://zoom.us/j/special-room');
    }

    public function test_student_can_change_granular_notification_settings(): void
    {
        [$user] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user);

        $this->telegramWebhook($this->callbackPayload('settings:payment', $account))->assertOk();
        $this->telegramWebhook($this->callbackPayload('settings:lead:120', $account))->assertOk();

        $account->refresh();
        $this->assertFalse($account->payment_notifications_enabled);
        $this->assertSame(120, $account->lesson_reminder_minutes);
        Http::assertSent(fn ($request) => str_contains($request->data()['text'] ?? '', 'Налаштування Telegram'));
    }

    public function test_homework_can_be_assigned_submitted_and_reviewed_in_telegram(): void
    {
        [$studentUser, $student] = $this->createStudentUser();
        [$teacherUser, $teacher] = $this->createTeacherUser();
        $studentAccount = $this->createTelegramAccount($studentUser, 'student');
        $teacherAccount = $this->createTelegramAccount($teacherUser, 'teacher');
        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDay()->addHour(),
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->callbackPayload(
            "homework:create:{$lesson->id}",
            $teacherAccount,
        ))->assertOk();
        $this->assertSame(
            $lesson->id,
            Cache::get("telegram:homework:teacher:{$teacherAccount->id}"),
        );

        $this->telegramWebhook($this->messagePayload(
            '',
            $teacherAccount,
            [
                'caption' => 'Complete exercises 1–5',
                'document' => [
                    'file_id' => 'teacher-document-file-id',
                    'file_name' => 'exercise.pdf',
                ],
            ],
        ))->assertOk();

        $assignment = TelegramHomeworkAssignment::query()->firstOrFail();
        $this->assertSame('Complete exercises 1–5', $assignment->text);
        $this->assertSame('teacher-document-file-id', $assignment->telegram_file_id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendDocument')
            && ($request->data()['chat_id'] ?? null) === $studentAccount->chat_id
            && ($request->data()['document'] ?? null) === 'teacher-document-file-id');

        $this->telegramWebhook($this->callbackPayload(
            "homework:submit:{$assignment->id}",
            $studentAccount,
        ))->assertOk();
        $this->telegramWebhook($this->messagePayload(
            '',
            $studentAccount,
            [
                'caption' => 'My completed work',
                'photo' => [[
                    'file_id' => 'student-photo-file-id',
                    'width' => 1280,
                    'height' => 720,
                ]],
            ],
        ))->assertOk();

        $submission = TelegramHomeworkSubmission::query()->firstOrFail();
        $this->assertSame(TelegramHomeworkSubmission::STATUS_SUBMITTED, $submission->status);
        $this->assertSame('student-photo-file-id', $submission->telegram_file_id);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendPhoto')
            && ($request->data()['chat_id'] ?? null) === $teacherAccount->chat_id
            && ($request->data()['photo'] ?? null) === 'student-photo-file-id');

        $this->telegramWebhook($this->callbackPayload(
            "homework:review:{$submission->id}",
            $teacherAccount,
        ))->assertOk();

        $this->assertSame(
            TelegramHomeworkSubmission::STATUS_REVIEWED,
            $submission->fresh()->status,
        );
        $this->assertNotNull($submission->fresh()->reviewed_at);
        Http::assertSent(fn ($request) => ($request->data()['chat_id'] ?? null) === $studentAccount->chat_id
            && str_contains($request->data()['text'] ?? '', 'Домашню роботу перевірено'));
    }

    private function createStudentUser(): array
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email_verified_at' => now(),
        ]);
        $student = Student::factory()->withoutTeacher()->create(['user_id' => $user->id]);

        return [$user, $student];
    }

    private function createTeacherUser(): array
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'email_verified_at' => now(),
        ]);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);

        return [$user, $teacher];
    }

    private function createTelegramAccount(User $user, string $suffix = 'student'): TelegramAccount
    {
        return TelegramAccount::query()->create([
            'user_id' => $user->id,
            'telegram_user_id' => $suffix === 'teacher' ? '800200' : '900100',
            'chat_id' => $suffix === 'teacher' ? '800201' : '900101',
            'username' => 'telegram_'.$suffix,
            'notifications_enabled' => true,
            'lesson_reminders_enabled' => true,
            'payment_notifications_enabled' => true,
            'homework_notifications_enabled' => true,
            'lesson_reminder_minutes' => 60,
            'connected_at' => now(),
        ]);
    }

    private function telegramWebhook(array $payload)
    {
        return $this->postJson(route('telegram.webhook'), $payload, [
            'X-Telegram-Bot-Api-Secret-Token' => 'test-webhook-secret',
        ]);
    }

    private function messagePayload(
        string $text,
        TelegramAccount $account,
        array $extra = [],
    ): array {
        return [
            'update_id' => 100,
            'message' => array_merge([
                'message_id' => 10,
                'from' => [
                    'id' => (int) $account->telegram_user_id,
                    'is_bot' => false,
                    'first_name' => 'Telegram user',
                ],
                'chat' => [
                    'id' => (int) $account->chat_id,
                    'type' => 'private',
                ],
                'text' => $text,
            ], $extra),
        ];
    }

    private function callbackPayload(string $data, TelegramAccount $account): array
    {
        return [
            'update_id' => 101,
            'callback_query' => [
                'id' => 'callback-enhancement',
                'from' => [
                    'id' => (int) $account->telegram_user_id,
                    'is_bot' => false,
                    'first_name' => 'Telegram user',
                ],
                'message' => [
                    'message_id' => 20,
                    'chat' => [
                        'id' => (int) $account->chat_id,
                        'type' => 'private',
                    ],
                ],
                'data' => $data,
            ],
        ];
    }
}
