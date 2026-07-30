<?php

namespace Tests\Feature;

use App\Enums\LessonStatus;
use App\Models\Group;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TelegramAccount;
use App\Models\TelegramNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramLessonReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-07-29 12:00:00');
        config(['services.telegram.bot_token' => 'test-bot-token']);

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_it_sends_one_reminder_for_an_upcoming_individual_lesson(): void
    {
        $this->fakeSuccessfulTelegram();

        [$student, $account] = $this->createLinkedStudent('101');
        $teacher = Teacher::factory()->create([
            'first_name' => 'Анна',
            'last_name' => 'Коваль',
        ]);
        $lesson = $this->createLesson([
            'title' => 'English <A1>',
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'lesson_type' => 'individual',
            'start_date' => now()->addMinutes(45),
        ]);

        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 1; sent: 1; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['chat_id'] === $account->chat_id
            && str_contains($request['text'], 'English &lt;A1&gt;')
            && str_contains($request['text'], '29.07.2026 12:45')
            && str_contains($request['text'], 'Індивідуальне')
            && str_contains($request['text'], 'Анна Коваль'));

        $this->assertDatabaseHas('telegram_notifications', [
            'telegram_account_id' => $account->id,
            'planned_lesson_id' => $lesson->id,
            'type' => TelegramNotification::TYPE_LESSON_REMINDER,
            'status' => TelegramNotification::STATUS_SENT,
            'attempts' => 1,
        ]);
    }

    public function test_it_notifies_both_student_and_teacher_with_role_specific_details(): void
    {
        $this->fakeSuccessfulTelegram();

        [$student, $studentAccount] = $this->createLinkedStudent('both-student');
        [$teacher, $teacherAccount] = $this->createLinkedTeacher('both-teacher');
        $lesson = $this->createLesson([
            'title' => 'Speaking practice',
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(25),
        ]);

        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 1; sent: 2; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['chat_id'] === $studentAccount->chat_id
            && str_contains($request['text'], 'Викладач:')
            && str_contains($request['text'], $teacher->full_name));
        Http::assertSent(fn ($request) => $request['chat_id'] === $teacherAccount->chat_id
            && str_contains($request['text'], 'Ваше заняття')
            && str_contains($request['text'], 'Учень:')
            && str_contains($request['text'], $student->full_name));

        $this->assertDatabaseHas('telegram_notifications', [
            'telegram_account_id' => $studentAccount->id,
            'planned_lesson_id' => $lesson->id,
            'status' => TelegramNotification::STATUS_SENT,
        ]);
        $this->assertDatabaseHas('telegram_notifications', [
            'telegram_account_id' => $teacherAccount->id,
            'planned_lesson_id' => $lesson->id,
            'status' => TelegramNotification::STATUS_SENT,
        ]);
    }

    public function test_teacher_receives_group_and_unassigned_trial_lesson_details(): void
    {
        $this->fakeSuccessfulTelegram();

        [$teacher, $account] = $this->createLinkedTeacher('group-trial');
        $group = Group::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'B1 Evening',
        ]);

        $this->createLesson([
            'teacher_id' => $teacher->id,
            'lesson_type' => 'group',
            'group_id' => $group->id,
            'start_date' => now()->addMinutes(20),
        ]);
        $this->createLesson([
            'teacher_id' => $teacher->id,
            'lesson_type' => 'trial',
            'student_id' => null,
            'group_id' => null,
            'start_date' => now()->addMinutes(40),
        ]);

        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 2; sent: 2; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request['chat_id'] === $account->chat_id
            && str_contains($request['text'], 'B1 Evening'));
        Http::assertSent(fn ($request) => $request['chat_id'] === $account->chat_id
            && str_contains($request['text'], 'пробне заняття без прив’язаного учня'));
    }

    public function test_group_and_pair_lessons_notify_each_linked_group_student(): void
    {
        foreach (['group', 'pair'] as $lessonType) {
            Http::fake([
                'api.telegram.org/*' => Http::response(['ok' => true]),
            ]);

            $group = Group::factory()->create(['type' => $lessonType]);
            [$firstStudent, $firstAccount] = $this->createLinkedStudent($lessonType.'-1', $group);
            [$secondStudent, $secondAccount] = $this->createLinkedStudent($lessonType.'-2', $group);
            $this->createStudent($group);
            $lesson = $this->createLesson([
                'title' => 'Group lesson',
                'lesson_type' => $lessonType,
                'student_id' => null,
                'group_id' => $group->id,
                'start_date' => now()->addMinutes(30),
            ]);

            $this->artisan('telegram:lessons:remind')->assertSuccessful();

            Http::assertSentCount(2);
            Http::assertSent(fn ($request) => in_array(
                $request['chat_id'],
                [$firstAccount->chat_id, $secondAccount->chat_id],
                true,
            ));
            $this->assertDatabaseCount('telegram_notifications', $lessonType === 'group' ? 2 : 4);
            $this->assertSame($group->id, $firstStudent->group_id);
            $this->assertSame($group->id, $secondStudent->group_id);
            $this->assertDatabaseHas('telegram_notifications', [
                'planned_lesson_id' => $lesson->id,
                'telegram_account_id' => $firstAccount->id,
                'status' => TelegramNotification::STATUS_SENT,
            ]);
            $this->assertDatabaseHas('telegram_notifications', [
                'planned_lesson_id' => $lesson->id,
                'telegram_account_id' => $secondAccount->id,
                'status' => TelegramNotification::STATUS_SENT,
            ]);
        }
    }

    public function test_it_does_not_send_duplicate_reminders(): void
    {
        $this->fakeSuccessfulTelegram();

        [$student] = $this->createLinkedStudent('duplicate');
        $this->createLesson([
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(40),
        ]);

        $this->artisan('telegram:lessons:remind')->assertSuccessful();
        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 1; sent: 0; failed: 0; skipped: 1.')
            ->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertDatabaseCount('telegram_notifications', 1);
    }

    public function test_it_retries_a_failed_delivery_and_stops_after_success(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::sequence()
                ->push(['ok' => false], 500)
                ->push(['ok' => true], 200),
        ]);

        [$student, $account] = $this->createLinkedStudent('retry');
        $lesson = $this->createLesson([
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(40),
        ]);

        $this->artisan('telegram:lessons:remind')->assertSuccessful();
        $this->assertDatabaseHas('telegram_notifications', [
            'telegram_account_id' => $account->id,
            'planned_lesson_id' => $lesson->id,
            'status' => TelegramNotification::STATUS_FAILED,
            'attempts' => 1,
        ]);
        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 1; sent: 1; failed: 0; skipped: 0.')
            ->assertSuccessful();
        $this->artisan('telegram:lessons:remind')->assertSuccessful();

        Http::assertSentCount(2);
        $this->assertDatabaseHas('telegram_notifications', [
            'telegram_account_id' => $account->id,
            'planned_lesson_id' => $lesson->id,
            'status' => TelegramNotification::STATUS_SENT,
            'attempts' => 2,
        ]);
    }

    public function test_it_does_not_retry_after_three_failed_attempts(): void
    {
        [$student, $account] = $this->createLinkedStudent('max-attempts');
        $lesson = $this->createLesson([
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(40),
        ]);
        TelegramNotification::query()->create([
            'telegram_account_id' => $account->id,
            'planned_lesson_id' => $lesson->id,
            'type' => TelegramNotification::TYPE_LESSON_REMINDER,
            'status' => TelegramNotification::STATUS_FAILED,
            'attempts' => 3,
            'last_attempt_at' => now()->subMinute(),
        ]);

        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 1; sent: 0; failed: 0; skipped: 1.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_it_ignores_lessons_outside_the_next_hour(): void
    {
        [$student] = $this->createLinkedStudent('time-window');

        $this->createLesson([
            'student_id' => $student->id,
            'start_date' => now()->subMinute(),
        ]);
        $this->createLesson([
            'student_id' => $student->id,
            'start_date' => now()->addHour()->addSecond(),
        ]);

        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 0; sent: 0; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_notifications', 0);
    }

    public function test_it_ignores_non_planned_and_soft_deleted_lessons(): void
    {
        [$student] = $this->createLinkedStudent('invalid-lessons');

        foreach ([
            LessonStatus::Completed,
            LessonStatus::Cancelled,
            LessonStatus::Rescheduled,
        ] as $status) {
            $this->createLesson([
                'student_id' => $student->id,
                'status' => $status,
                'start_date' => now()->addMinutes(30),
            ]);
        }

        $deletedLesson = $this->createLesson([
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(30),
        ]);
        $deletedLesson->delete();

        $this->artisan('telegram:lessons:remind')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_notifications', 0);
    }

    public function test_it_ignores_trial_without_student_and_disabled_notifications(): void
    {
        $this->createLesson([
            'lesson_type' => 'trial',
            'student_id' => null,
            'group_id' => null,
            'start_date' => now()->addMinutes(30),
        ]);

        [$student, $account] = $this->createLinkedStudent('disabled');
        $account->update(['notifications_enabled' => false]);
        $this->createLesson([
            'student_id' => $student->id,
            'start_date' => now()->addMinutes(30),
        ]);

        $this
            ->artisan('telegram:lessons:remind')
            ->expectsOutput('Lessons: 2; sent: 0; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_notifications', 0);
    }

    private function createLinkedStudent(
        string $suffix,
        ?Group $group = null,
    ): array {
        $student = $this->createStudent($group);
        $account = TelegramAccount::query()->create([
            'user_id' => $student->user_id,
            'telegram_user_id' => 'telegram-'.$suffix,
            'chat_id' => 'chat-'.$suffix,
            'username' => 'student_'.$suffix,
            'connected_at' => now(),
        ]);

        return [$student, $account];
    }

    private function fakeSuccessfulTelegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }

    private function createLinkedTeacher(string $suffix): array
    {
        $user = User::factory()->create([
            'role' => 'teacher',
            'email_verified_at' => now(),
        ]);
        $teacher = Teacher::factory()->create(['user_id' => $user->id]);
        $account = TelegramAccount::query()->create([
            'user_id' => $user->id,
            'telegram_user_id' => 'telegram-'.$suffix,
            'chat_id' => 'chat-'.$suffix,
            'username' => 'teacher_'.$suffix,
            'connected_at' => now(),
        ]);

        return [$teacher, $account];
    }

    private function createStudent(?Group $group = null): Student
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email_verified_at' => now(),
        ]);

        return Student::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group?->id,
            'teacher_id' => $group?->teacher_id,
        ]);
    }

    private function createLesson(array $attributes = []): PlannedLesson
    {
        return PlannedLesson::factory()->create(array_merge([
            'lesson_type' => 'individual',
            'student_id' => null,
            'group_id' => null,
            'status' => LessonStatus::Planned,
            'start_date' => now()->addMinutes(30),
            'end_date' => now()->addMinutes(90),
        ], $attributes));
    }
}
