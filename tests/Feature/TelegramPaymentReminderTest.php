<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\TelegramAccount;
use App\Models\TelegramPaymentReminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramPaymentReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-08-09 09:00:00');
        config([
            'app.url' => 'https://school.test',
            'services.telegram.bot_token' => 'test-bot-token',
        ]);

        Http::preventStrayRequests();
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_it_reminds_a_linked_student_when_the_current_month_is_unpaid(): void
    {
        $this->fakeSuccessfulTelegram();
        [$student, $account, $template] = $this->createLinkedStudent('due');

        $this
            ->artisan('telegram:payments:remind')
            ->expectsOutput('Reminders: 1; sent: 1; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertSentCount(1);
        $request = Http::recorded()->first()[0];
        $this->assertSame($account->chat_id, $request['chat_id']);
        $this->assertStringContainsString('Нагадування про оплату', $request['text']);
        $this->assertStringContainsString($template->title, $request['text']);
        $this->assertStringContainsString('2 800.00 грн', $request['text']);
        $this->assertSame(
            'Перейти до оплати',
            data_get($request['reply_markup'], 'inline_keyboard.0.0.text'),
        );
        $this->assertSame(
            'http://school.test/student/payments',
            data_get($request['reply_markup'], 'inline_keyboard.0.0.url'),
        );

        $this->assertDatabaseHas('telegram_payment_reminders', [
            'telegram_account_id' => $account->id,
            'student_id' => $student->id,
            'payment_month' => '2026-08-01',
            'stage' => TelegramPaymentReminder::STAGE_DUE,
            'status' => TelegramPaymentReminder::STATUS_SENT,
            'attempts' => 1,
        ]);
    }

    public function test_it_sends_an_upcoming_reminder_seven_days_before_next_month(): void
    {
        $this->travelTo('2026-08-25 09:00:00');
        $this->fakeSuccessfulTelegram();
        [$student, $account, $template] = $this->createLinkedStudent('upcoming');
        $this->payMonth($student, $template, '2026-08-01', '2026-08-31');

        $this->artisan('telegram:payments:remind')->assertSuccessful();

        Http::assertSent(fn ($request) => $request['chat_id'] === $account->chat_id
            && str_contains($request['text'], 'на наступний місяць')
            && str_contains($request['text'], 'вересень 2026'));
        $this->assertDatabaseHas('telegram_payment_reminders', [
            'telegram_account_id' => $account->id,
            'payment_month' => '2026-09-01',
            'stage' => TelegramPaymentReminder::STAGE_UPCOMING,
            'status' => TelegramPaymentReminder::STATUS_SENT,
        ]);
    }

    public function test_it_does_not_remind_when_the_relevant_month_is_paid(): void
    {
        [$student, , $template] = $this->createLinkedStudent('paid');
        $this->payMonth($student, $template, '2026-08-01', '2026-08-31');

        $this
            ->artisan('telegram:payments:remind')
            ->expectsOutput('Reminders: 0; sent: 0; failed: 0; skipped: 0.')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_payment_reminders', 0);
    }

    public function test_it_does_not_send_duplicate_payment_reminders(): void
    {
        $this->fakeSuccessfulTelegram();
        $this->createLinkedStudent('duplicate');

        $this->artisan('telegram:payments:remind')->assertSuccessful();
        $this
            ->artisan('telegram:payments:remind')
            ->expectsOutput('Reminders: 1; sent: 0; failed: 0; skipped: 1.')
            ->assertSuccessful();

        Http::assertSentCount(1);
        $this->assertDatabaseCount('telegram_payment_reminders', 1);
    }

    public function test_it_ignores_disabled_accounts_and_students_without_a_template(): void
    {
        [, $disabledAccount] = $this->createLinkedStudent('disabled');
        $disabledAccount->update(['notifications_enabled' => false]);

        [$student] = $this->createLinkedStudent('no-template');
        $student->update(['subscription_id' => null]);

        $this->artisan('telegram:payments:remind')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertDatabaseCount('telegram_payment_reminders', 0);
    }

    private function createLinkedStudent(string $suffix): array
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email_verified_at' => now(),
        ]);
        $template = SubscriptionTemplate::factory()->create([
            'title' => 'Індивідуальний місячний',
            'price' => 2800,
        ]);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
            'is_active' => true,
        ]);
        $account = TelegramAccount::query()->create([
            'user_id' => $user->id,
            'telegram_user_id' => 'telegram-'.$suffix,
            'chat_id' => 'chat-'.$suffix,
            'notifications_enabled' => true,
            'connected_at' => now(),
        ]);

        return [$student, $account, $template];
    }

    private function payMonth(
        Student $student,
        SubscriptionTemplate $template,
        string $start,
        string $end,
    ): StudentSubscription {
        return StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => $start,
            'end_date' => $end,
            'price' => $template->price,
        ]);
    }

    private function fakeSuccessfulTelegram(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true]),
        ]);
    }
}
