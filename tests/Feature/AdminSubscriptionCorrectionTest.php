<?php

namespace Tests\Feature;

use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_correction_routes_reject_invalid_source_months(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create();

        $this
            ->actingAs($admin)
            ->delete("/admin/students/{$student->id}/subscriptions/2026-13")
            ->assertNotFound();

        $this
            ->actingAs($admin)
            ->putJson("/admin/students/{$student->id}/subscriptions/not-a-month/move", [
                'target_month' => '2026-07',
            ])
            ->assertNotFound();
    }

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_records_manual_refund_without_deleting_financial_history(): void
    {
        [$student, $subscription, $payment] = $this->paidSubscription();

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.students.subscriptions.destroyMonth', [
                $student,
                '2026-08',
            ]))
            ->assertOk()
            ->assertJson([
                'message' => 'Абонемент скасовано, ручне повернення зафіксовано.',
            ]);

        $this->assertSame('cancelled', $subscription->fresh()->status);
        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertTrue($payment->fresh()->payload['refunded_manually_by_admin']);
        $this->assertDatabaseHas('student_subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);
    }

    public function test_admin_can_cancel_manually_added_subscription_without_online_payment(): void
    {
        $student = Student::factory()->create();
        $template = SubscriptionTemplate::factory()->create(['type' => 'individual']);
        $subscription = StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'payment_id' => null,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.students.subscriptions.destroyMonth', [
                $student,
                '2026-08',
            ]))
            ->assertOk();

        $this->assertSame('cancelled', $subscription->fresh()->status);
    }

    public function test_refund_is_blocked_after_first_recorded_lesson(): void
    {
        [$student, $subscription, $payment] = $this->paidSubscription();

        LessonLog::factory()->create([
            'student_id' => $student->id,
            'lesson_type' => 'individual',
            'status' => 'completed',
            'date' => '2026-08-10',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.students.subscriptions.destroyMonth', [
                $student,
                '2026-08',
            ]))
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Повернення неможливе: за абонементом уже є проведене або зараховане заняття.',
            ]);

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_admin_moves_subscription_and_its_payment_to_another_month(): void
    {
        [$student, $subscription, $payment] = $this->paidSubscription();

        $this->actingAs($this->admin)
            ->putJson(route('admin.students.subscriptions.moveMonth', [
                $student,
                '2026-08',
            ]), [
                'target_month' => '2026-09',
            ])
            ->assertOk()
            ->assertJson(['message' => 'Абонемент успішно перенесено.']);

        $subscription->refresh();
        $payment->refresh();

        $this->assertSame('2026-09-01', $subscription->start_date->toDateString());
        $this->assertSame('2026-09-30', $subscription->end_date->toDateString());
        $this->assertSame('paid', $payment->status);
        $this->assertSame('2026-09', $payment->payload['subscription_month']);
        $this->assertSame('2026-08', $payment->payload['moved_by_admin_from_month']);
        $this->assertSame(
            'Оплата за навчання за період вересень 2026 - Petrenko Ivan',
            $payment->description
        );
    }

    public function test_move_is_blocked_after_lesson_or_when_target_month_is_occupied(): void
    {
        [$student, $subscription] = $this->paidSubscription();

        LessonLog::factory()->create([
            'student_id' => $student->id,
            'lesson_type' => 'individual',
            'status' => 'charged',
            'date' => '2026-08-10',
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.students.subscriptions.moveMonth', [
                $student,
                '2026-08',
            ]), [
                'target_month' => '2026-09',
            ])
            ->assertUnprocessable();

        $this->assertSame('2026-08-01', $subscription->fresh()->start_date->toDateString());

        LessonLog::query()->delete();
        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
        ]);

        $this->actingAs($this->admin)
            ->putJson(route('admin.students.subscriptions.moveMonth', [
                $student,
                '2026-08',
            ]), [
                'target_month' => '2026-09',
            ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'На вибраний місяць уже є активний абонемент.',
            ]);
    }

    public function test_archiving_template_keeps_student_assignment_and_payment_history(): void
    {
        [$student, $subscription, $payment] = $this->paidSubscription();
        $template = $subscription->subscriptionTemplate;
        $student->update(['subscription_id' => $template->id]);

        $this
            ->actingAs($this->admin)
            ->delete(route('admin.subscription-templates.destroy', $template))
            ->assertRedirect(route('admin.subscription-templates.index'));

        $this->assertDatabaseHas('subscription_templates', [
            'id' => $template->id,
            'is_active' => false,
        ]);
        $this->assertDatabaseHas('student_subscriptions', [
            'id' => $subscription->id,
            'subscription_template_id' => $template->id,
        ]);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);
        $this->assertSame($template->id, $student->fresh()->subscription_id);
    }

    private function paidSubscription(): array
    {
        $student = Student::factory()->create([
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
        ]);
        $template = SubscriptionTemplate::factory()->create([
            'type' => 'individual',
            'price' => 2800,
        ]);
        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'paid',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_payment_id' => 'invoice-subscription',
            'provider_order_id' => 'order-subscription',
            'description' => 'August subscription',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
            'paid_at' => now(),
        ]);
        $subscription = StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'payment_id' => $payment->id,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'price' => 2800,
        ]);

        return [$student, $subscription, $payment];
    }
}
