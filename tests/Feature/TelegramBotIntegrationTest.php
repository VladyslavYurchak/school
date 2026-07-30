<?php

namespace Tests\Feature;

use App\Models\PlannedLesson;
use App\Models\Group;
use App\Models\LessonLog;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\Teacher;
use App\Models\TelegramAccount;
use App\Models\TelegramLinkToken;
use App\Models\User;
use App\Services\Telegram\TelegramLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TelegramBotIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-bot-token',
            'services.telegram.bot_username' => 'KorporatsiiaMovTestBot',
            'services.telegram.webhook_secret' => 'test-webhook-secret',
        ]);

        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []]),
        ]);
    }

    public function test_student_can_create_a_short_lived_hashed_link_token(): void
    {
        [$user] = $this->createStudentUser();

        $response = $this
            ->actingAs($user)
            ->post(route('student.telegram.connect'));

        $location = $response->headers->get('Location');

        $this->assertNotNull($location);
        $this->assertStringStartsWith('https://t.me/KorporatsiiaMovTestBot?start=', $location);

        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $plainToken = $query['start'] ?? '';

        $this->assertSame(48, strlen($plainToken));
        $this->assertDatabaseHas('telegram_link_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'used_at' => null,
        ]);
        $this->assertDatabaseMissing('telegram_link_tokens', [
            'token_hash' => $plainToken,
        ]);
        $this->assertTrue(
            TelegramLinkToken::query()->firstOrFail()->expires_at->isBetween(
                now()->addMinutes(14),
                now()->addMinutes(16),
            )
        );
    }

    public function test_non_student_cannot_create_telegram_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->post(route('student.telegram.connect'))
            ->assertForbidden();

        $this->assertDatabaseCount('telegram_link_tokens', 0);
    }

    public function test_teacher_can_create_a_telegram_link_and_manage_it_from_dashboard(): void
    {
        [$user] = $this->createTeacherUser();

        $this
            ->actingAs($user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Підключити Telegram')
            ->assertSee(route('teacher.telegram.connect'), false);

        $response = $this
            ->actingAs($user)
            ->post(route('teacher.telegram.connect'));

        $this->assertStringStartsWith(
            'https://t.me/KorporatsiiaMovTestBot?start=',
            (string) $response->headers->get('Location'),
        );
        $this->assertDatabaseHas('telegram_link_tokens', ['user_id' => $user->id]);

        $this->createTelegramAccount($user, 'teacher');

        $this
            ->actingAs($user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Від’єднати Telegram')
            ->assertSee('@telegram_teacher')
            ->assertSee(route('teacher.telegram.disconnect'), false);

        $this
            ->actingAs($user)
            ->delete(route('teacher.telegram.disconnect'))
            ->assertRedirect()
            ->assertSessionHas('telegram_success');

        $this->assertDatabaseMissing('telegram_accounts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('telegram_link_tokens', ['user_id' => $user->id]);
    }

    public function test_connect_route_fails_safely_when_bot_username_is_missing(): void
    {
        [$user] = $this->createStudentUser();
        config(['services.telegram.bot_username' => null]);

        $this
            ->actingAs($user)
            ->from(route('student.dashboard'))
            ->post(route('student.telegram.connect'))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('telegram_error');

        $this->assertDatabaseCount('telegram_link_tokens', 0);
    }

    public function test_webhook_rejects_missing_or_wrong_secret(): void
    {
        $payload = $this->messagePayload('/start invalid');

        $this->postJson(route('telegram.webhook'), $payload)->assertForbidden();
        $this
            ->postJson(route('telegram.webhook'), $payload, [
                'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
            ])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_webhook_ignores_group_messages_and_messages_from_bots(): void
    {
        $groupPayload = $this->messagePayload('/start token');
        $groupPayload['message']['chat']['type'] = 'group';

        $botPayload = $this->messagePayload('/start token');
        $botPayload['message']['from']['is_bot'] = true;

        $this->telegramWebhook($groupPayload)->assertOk();
        $this->telegramWebhook($botPayload)->assertOk();

        $this->assertDatabaseCount('telegram_accounts', 0);
        Http::assertNothingSent();
    }

    public function test_artisan_command_registers_https_webhook_with_secret(): void
    {
        config(['app.url' => 'https://school.example']);
        URL::forceRootUrl('https://school.example');
        URL::forceScheme('https');

        $this->assertSame(
            'https://school.example/telegram/webhook',
            route('telegram.webhook'),
        );

        $this
            ->artisan('telegram:webhook:set')
            ->expectsOutputToContain('Telegram webhook registered')
            ->assertSuccessful();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/setWebhook')
            && $request['url'] === 'https://school.example/telegram/webhook'
            && $request['secret_token'] === 'test-webhook-secret'
            && $request['allowed_updates'] === ['message', 'callback_query']);
    }

    public function test_artisan_command_rejects_unsafe_webhook_secret(): void
    {
        config([
            'app.url' => 'https://school.example',
            'services.telegram.webhook_secret' => 'not allowed * secret',
        ]);

        $this
            ->artisan('telegram:webhook:set')
            ->expectsOutputToContain('must contain only')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_valid_start_command_links_telegram_to_student_once(): void
    {
        [$user] = $this->createStudentUser();
        $plainToken = app(TelegramLinkService::class)->issue($user);

        $this->telegramWebhook($this->messagePayload(
            "/start {$plainToken}",
            chatId: 77112233,
            telegramUserId: 99112233,
            username: 'student_one',
        ))->assertOk();

        $this->assertDatabaseHas('telegram_accounts', [
            'user_id' => $user->id,
            'telegram_user_id' => '99112233',
            'chat_id' => '77112233',
            'username' => 'student_one',
            'notifications_enabled' => true,
        ]);
        $this->assertNotNull(TelegramLinkToken::query()->firstOrFail()->used_at);

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && $request['chat_id'] === '77112233'
            && str_contains($request['text'], 'успішно підключено')
            && isset($request['reply_markup']['keyboard']));
    }

    public function test_valid_start_command_links_teacher_and_shows_teacher_menu(): void
    {
        [$user] = $this->createTeacherUser();
        $plainToken = app(TelegramLinkService::class)->issue($user);

        $this->telegramWebhook($this->messagePayload(
            "/start {$plainToken}",
            chatId: 88112233,
            telegramUserId: 88112233,
            username: 'teacher_one',
        ))->assertOk();

        $this->assertDatabaseHas('telegram_accounts', [
            'user_id' => $user->id,
            'chat_id' => '88112233',
        ]);

        Http::assertSent(fn ($request) => $request['chat_id'] === '88112233'
            && ($request['reply_markup']['keyboard'][0][0]['text'] ?? null) === 'Мій розклад'
            && ! str_contains(json_encode($request['reply_markup']), 'Мій абонемент'));
    }

    public function test_expired_or_reused_token_cannot_link_an_account(): void
    {
        [$user] = $this->createStudentUser();
        $plainToken = app(TelegramLinkService::class)->issue($user);
        TelegramLinkToken::query()->update(['expires_at' => now()->subMinute()]);

        $this->telegramWebhook($this->messagePayload("/start {$plainToken}"))->assertOk();

        $this->assertDatabaseCount('telegram_accounts', 0);

        TelegramLinkToken::query()->update([
            'expires_at' => now()->addMinutes(10),
            'used_at' => now(),
        ]);

        $this->telegramWebhook($this->messagePayload("/start {$plainToken}"))->assertOk();

        $this->assertDatabaseCount('telegram_accounts', 0);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'недійсне або вже прострочене'));
    }

    public function test_same_telegram_cannot_be_linked_to_another_student(): void
    {
        [$firstUser] = $this->createStudentUser();
        [$secondUser] = $this->createStudentUser();

        TelegramAccount::query()->create([
            'user_id' => $firstUser->id,
            'telegram_user_id' => '10001',
            'chat_id' => '20001',
            'connected_at' => now(),
        ]);

        $plainToken = app(TelegramLinkService::class)->issue($secondUser);

        $this->telegramWebhook($this->messagePayload(
            "/start {$plainToken}",
            chatId: 20001,
            telegramUserId: 10001,
        ))->assertOk();

        $this->assertDatabaseCount('telegram_accounts', 1);
        $this->assertDatabaseMissing('telegram_accounts', ['user_id' => $secondUser->id]);
        $this->assertNull(TelegramLinkToken::query()->firstOrFail()->used_at);
        Http::assertSent(fn ($request) => str_contains($request['text'], 'іншого кабінету'));
    }

    public function test_linked_student_sees_only_their_upcoming_lessons(): void
    {
        [$user, $student] = $this->createStudentUser();
        [, $otherStudent] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user);

        PlannedLesson::factory()->individual()->create([
            'student_id' => $student->id,
            'title' => 'My future lesson',
            'start_date' => now()->addDay()->setTime(15, 30),
            'end_date' => now()->addDay()->setTime(16, 30),
            'status' => 'planned',
        ]);
        PlannedLesson::factory()->individual()->create([
            'student_id' => $otherStudent->id,
            'title' => 'Another student lesson',
            'start_date' => now()->addDay()->setTime(17, 0),
            'end_date' => now()->addDay()->setTime(18, 0),
            'status' => 'planned',
        ]);
        PlannedLesson::factory()->individual()->create([
            'student_id' => $student->id,
            'title' => 'Past lesson',
            'start_date' => now()->subDay(),
            'end_date' => now()->subDay()->addHour(),
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->messagePayload(
            'Мої заняття',
            chatId: (int) $account->chat_id,
            telegramUserId: (int) $account->telegram_user_id,
        ))->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request['text'], 'My future lesson')
                && str_contains($request['text'], '15:30')
                && ! str_contains($request['text'], 'Another student lesson')
                && ! str_contains($request['text'], 'Past lesson');
        });
    }

    public function test_linked_student_can_view_active_subscription(): void
    {
        [$user, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_title' => 'Індивідуальний місячний',
            'subscription_template_id' => null,
            'status' => 'active',
            'type' => 'subscription',
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ]);

        $this->telegramWebhook($this->messagePayload(
            'Мій абонемент',
            chatId: (int) $account->chat_id,
            telegramUserId: (int) $account->telegram_user_id,
        ))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'], 'Індивідуальний місячний')
            && str_contains($request['text'], 'Статус: активний'));
    }

    public function test_linked_teacher_sees_only_their_upcoming_lessons(): void
    {
        [$user, $teacher] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();
        [$studentUser, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user, 'teacher');

        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'title' => 'My teacher lesson',
            'start_date' => now()->addDay()->setTime(15, 30),
            'end_date' => now()->addDay()->setTime(16, 30),
            'status' => 'planned',
        ]);
        PlannedLesson::factory()->individual()->create([
            'teacher_id' => $otherTeacher->id,
            'student_id' => $student->id,
            'title' => 'Other teacher lesson',
            'start_date' => now()->addDay()->setTime(17, 0),
            'end_date' => now()->addDay()->setTime(18, 0),
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->messagePayload(
            'Мій розклад',
            chatId: (int) $account->chat_id,
            telegramUserId: (int) $account->telegram_user_id,
        ))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'], 'My teacher lesson')
            && str_contains($request['text'], $student->full_name)
            && ! str_contains($request['text'], 'Other teacher lesson')
            && ($request['reply_markup']['inline_keyboard'][0][0]['text'] ?? null) === 'Проведено'
            && ($request['reply_markup']['inline_keyboard'][0][1]['text'] ?? null) === 'Перенести'
            && ($request['reply_markup']['inline_keyboard'][1][0]['text'] ?? null) === 'Скасувати');
    }

    public function test_teacher_can_see_recent_unresolved_lesson_after_it_ended(): void
    {
        [$user, $teacher] = $this->createTeacherUser();
        [, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user, 'teacher');
        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'title' => 'Recently ended lesson',
            'start_date' => now()->subHours(2),
            'end_date' => now()->subHour(),
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->messagePayload(
            'Мій розклад',
            chatId: (int) $account->chat_id,
            telegramUserId: (int) $account->telegram_user_id,
        ))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'], 'Recently ended lesson')
            && ($request['reply_markup']['inline_keyboard'][0][0]['callback_data'] ?? null)
                === "lesson:complete:{$lesson->id}");
    }

    public function test_teacher_can_complete_own_individual_lesson_from_telegram(): void
    {
        [$user, $teacher] = $this->createTeacherUser();
        [, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user, 'teacher');
        $teacher->update(['lesson_price' => 450]);
        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => now()->subHour(),
            'end_date' => now(),
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->callbackPayload(
            "lesson:complete:{$lesson->id}",
            $account,
        ))->assertOk();

        $this->assertSame('completed', $lesson->fresh()->status->value);
        $this->assertDatabaseHas('lesson_logs', [
            'lesson_id' => $lesson->id,
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'status' => 'completed',
            'teacher_payout_amount' => 450,
        ]);
        $this->assertDatabaseHas('lesson_actions', [
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'action' => 'completed',
        ]);
    }

    public function test_group_completion_requires_confirmation_and_records_everyone_present(): void
    {
        [$user, $teacher] = $this->createTeacherUser();
        $account = $this->createTelegramAccount($user, 'teacher');
        $teacher->update(['group_lesson_price' => 600]);
        $group = Group::factory()->create(['teacher_id' => $teacher->id]);
        $first = Student::factory()->create(['group_id' => $group->id]);
        $second = Student::factory()->create(['group_id' => $group->id]);
        $lesson = PlannedLesson::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'lesson_type' => 'group',
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->callbackPayload(
            "lesson:complete:{$lesson->id}",
            $account,
        ))->assertOk();

        $this->assertSame('planned', $lesson->fresh()->status->value);
        $this->assertDatabaseCount('lesson_logs', 0);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/sendMessage')
            && ($request['reply_markup']['inline_keyboard'][0][0]['callback_data'] ?? null)
                === "lesson:complete_all:{$lesson->id}");

        $this->telegramWebhook($this->callbackPayload(
            "lesson:complete_all:{$lesson->id}",
            $account,
        ))->assertOk();

        $this->assertSame('completed', $lesson->fresh()->status->value);
        $this->assertDatabaseHas('lesson_logs', [
            'lesson_id' => $lesson->id,
            'student_id' => $first->id,
            'status' => 'completed',
            'teacher_payout_amount' => 300,
        ]);
        $this->assertDatabaseHas('lesson_logs', [
            'lesson_id' => $lesson->id,
            'student_id' => $second->id,
            'status' => 'completed',
            'teacher_payout_amount' => 300,
        ]);
    }

    public function test_teacher_can_cancel_own_lesson_and_its_logs_from_telegram(): void
    {
        [$user, $teacher] = $this->createTeacherUser();
        [, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user, 'teacher');
        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'status' => 'planned',
        ]);
        LessonLog::factory()->create([
            'lesson_id' => $lesson->id,
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
        ]);

        $this->telegramWebhook($this->callbackPayload(
            "lesson:cancel_confirm:{$lesson->id}",
            $account,
        ))->assertOk();

        $cancelled = PlannedLesson::withTrashed()->findOrFail($lesson->id);
        $this->assertSame('cancelled', $cancelled->status->value);
        $this->assertNotNull($cancelled->deleted_at);
        $this->assertDatabaseMissing('lesson_logs', ['lesson_id' => $lesson->id]);
        $this->assertDatabaseHas('lesson_actions', [
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'action' => 'cancelled',
        ]);
    }

    public function test_teacher_can_reschedule_own_lesson_by_sending_date_and_time(): void
    {
        [$user, $teacher] = $this->createTeacherUser();
        [, $student] = $this->createStudentUser();
        $account = $this->createTelegramAccount($user, 'teacher');
        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-08-01 15:00:00',
            'end_date' => '2026-08-01 16:30:00',
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->callbackPayload(
            "lesson:reschedule:{$lesson->id}",
            $account,
        ))->assertOk();

        $this->assertSame(
            $lesson->id,
            Cache::get("telegram:teacher:{$account->id}:pending-reschedule"),
        );

        $this->telegramWebhook($this->messagePayload(
            '03.08.2026 18:15',
            chatId: (int) $account->chat_id,
            telegramUserId: (int) $account->telegram_user_id,
            username: $account->username,
        ))->assertOk();

        $oldLesson = PlannedLesson::withTrashed()->findOrFail($lesson->id);
        $newLesson = PlannedLesson::query()
            ->where('id', '!=', $lesson->id)
            ->where('teacher_id', $teacher->id)
            ->firstOrFail();

        $this->assertSame('rescheduled', $oldLesson->status->value);
        $this->assertNotNull($oldLesson->deleted_at);
        $this->assertSame('2026-08-03 18:15:00', $newLesson->start_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-03 19:45:00', $newLesson->end_date->format('Y-m-d H:i:s'));
        $this->assertNull(Cache::get("telegram:teacher:{$account->id}:pending-reschedule"));
    }

    public function test_teacher_cannot_change_another_teachers_lesson_from_telegram(): void
    {
        [$user] = $this->createTeacherUser();
        [, $otherTeacher] = $this->createTeacherUser();
        $account = $this->createTelegramAccount($user, 'teacher');
        $lesson = PlannedLesson::factory()->individual()->create([
            'teacher_id' => $otherTeacher->id,
            'status' => 'planned',
        ]);

        $this->telegramWebhook($this->callbackPayload(
            "lesson:complete:{$lesson->id}",
            $account,
        ))->assertOk();

        $this->assertSame('planned', $lesson->fresh()->status->value);
        $this->assertDatabaseCount('lesson_logs', 0);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/answerCallbackQuery')
            && str_contains($request['text'], 'недоступне'));
    }

    public function test_unlinked_chat_cannot_read_student_information(): void
    {
        $this->createStudentUser();

        $this->telegramWebhook($this->messagePayload('Мої заняття'))->assertOk();

        Http::assertSent(fn ($request) => str_contains($request['text'], 'Спочатку підключіть Telegram'));
    }

    public function test_student_can_disconnect_telegram_from_dashboard(): void
    {
        [$user] = $this->createStudentUser();
        $this->createTelegramAccount($user);
        TelegramLinkToken::query()->create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'old-token'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this
            ->actingAs($user)
            ->delete(route('student.telegram.disconnect'))
            ->assertRedirect()
            ->assertSessionHas('telegram_success');

        $this->assertDatabaseCount('telegram_accounts', 0);
        $this->assertDatabaseCount('telegram_link_tokens', 0);
    }

    public function test_student_dashboard_shows_correct_telegram_action(): void
    {
        [$user] = $this->createStudentUser();

        $this
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Підключити Telegram')
            ->assertSee(route('student.telegram.connect'), false);

        $this->createTelegramAccount($user);

        $this
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Від’єднати Telegram')
            ->assertSee('@telegram_student')
            ->assertSee(route('student.telegram.disconnect'), false);
    }

    private function createStudentUser(): array
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email_verified_at' => now(),
        ]);
        $student = Student::factory()->withoutTeacher()->create([
            'user_id' => $user->id,
        ]);

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

    private function createTelegramAccount(
        User $user,
        string $suffix = 'student',
    ): TelegramAccount {
        return TelegramAccount::query()->create([
            'user_id' => $user->id,
            'telegram_user_id' => $suffix === 'student' ? '99112233' : '88112233',
            'chat_id' => $suffix === 'student' ? '77112233' : '88112233',
            'username' => 'telegram_'.$suffix,
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
        int $chatId = 77112233,
        int $telegramUserId = 99112233,
        ?string $username = 'telegram_student',
    ): array {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'from' => array_filter([
                    'id' => $telegramUserId,
                    'is_bot' => false,
                    'first_name' => 'Student',
                    'username' => $username,
                ], fn ($value) => $value !== null),
                'chat' => [
                    'id' => $chatId,
                    'type' => 'private',
                ],
                'text' => $text,
            ],
        ];
    }

    private function callbackPayload(string $data, TelegramAccount $account): array
    {
        return [
            'update_id' => 2,
            'callback_query' => [
                'id' => 'callback-1',
                'from' => [
                    'id' => (int) $account->telegram_user_id,
                    'is_bot' => false,
                    'first_name' => 'Teacher',
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
