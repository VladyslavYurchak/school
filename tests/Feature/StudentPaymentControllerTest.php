<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_page_defaults_to_first_unpaid_subscription_month(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'price' => 2800,
        ]);

        $this
            ->actingAs($user)
            ->get(route('student.payments.index'))
            ->assertOk()
            ->assertSee('<select', false)
            ->assertSee('name="subscription_month"', false)
            ->assertSee('value="2026-06"', false)
            ->assertSee('value="2026-10"', false)
            ->assertSee('value="2026-09" selected', false)
            ->assertSee('вересень 2026')
            ->assertDontSee('name="subscription_template_id"', false);
    }

    public function test_student_creates_pending_monopay_payment_for_assigned_subscription_month(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'title' => 'Individual discount plan',
            'price' => 2800,
            'type' => 'individual',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
            ]);

        $payment = Payment::firstOrFail();

        $response->assertRedirect(route('student.payments.checkout', $payment));

        $this->assertSame($student->id, $payment->student_id);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('subscription', $payment->type);
        $this->assertSame('monopay', $payment->provider);
        $this->assertEquals(2800, (float) $payment->amount);
        $this->assertSame($template->id, $payment->payload['subscription_template_id']);
        $this->assertSame('2026-08', $payment->payload['subscription_month']);
    }

    public function test_student_can_create_subscription_payment_for_edge_months_in_allowed_window(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-06',
            ])
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-10',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_student_cannot_create_subscription_payment_outside_allowed_month_window(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-05',
            ])
            ->assertRedirect(route('student.payments.index'))
            ->assertSessionHasErrors('subscription_month');

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-11',
            ])
            ->assertRedirect(route('student.payments.index'))
            ->assertSessionHasErrors('subscription_month');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_student_cannot_create_subscription_payment_without_assigned_template(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
            ])
            ->assertStatus(422);

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_student_cannot_create_subscription_payment_for_already_active_month(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
            'price' => 2800,
        ]);

        $this
            ->actingAs($user)
            ->from(route('student.payments.index'))
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
            ])
            ->assertRedirect(route('student.payments.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_student_reuses_existing_fresh_pending_subscription_payment_for_same_month(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        $existingPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'existing-order',
            'provider_payment_id' => 'fresh-invoice',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
                'mono_invoice' => [
                    'invoiceId' => 'fresh-invoice',
                    'pageUrl' => 'https://example.test/fresh-invoice',
                ],
            ],
        ]);

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
            ])
            ->assertRedirect(route('student.payments.checkout', $existingPayment));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_student_replaces_expired_pending_subscription_payment_for_same_month(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        $oldPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'old-order',
            'provider_payment_id' => 'old-invoice',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
                'mono_invoice' => [
                    'invoiceId' => 'old-invoice',
                    'pageUrl' => 'https://example.test/old-invoice',
                ],
            ],
        ]);
        $oldPayment->forceFill([
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ])->save();

        $response = $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
            ]);

        $newPayment = Payment::query()
            ->where('id', '!=', $oldPayment->id)
            ->firstOrFail();

        $response->assertRedirect(route('student.payments.checkout', $newPayment));

        $oldPayment->refresh();

        $this->assertSame('failed', $oldPayment->status);
        $this->assertTrue($oldPayment->payload['expired_locally']);
        $this->assertSame('pending', $newPayment->status);
        $this->assertSame($template->id, $newPayment->payload['subscription_template_id']);
        $this->assertSame('2026-08', $newPayment->payload['subscription_month']);
    }

    public function test_student_reuses_pending_subscription_payment_without_invoice_yet(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        $existingPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'no-invoice-order',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
            ])
            ->assertRedirect(route('student.payments.checkout', $existingPayment));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_student_payment_uses_assigned_template_and_ignores_submitted_template_id(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $assignedTemplate = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'type' => 'individual',
        ]);
        $otherTemplate = SubscriptionTemplate::factory()->create([
            'price' => 100,
            'type' => 'group',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $assignedTemplate->id,
        ]);

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
                'subscription_template_id' => $otherTemplate->id,
            ])
            ->assertRedirect();

        $payment = Payment::firstOrFail();

        $this->assertSame($student->id, $payment->student_id);
        $this->assertSame($assignedTemplate->id, $payment->payload['subscription_template_id']);
        $this->assertEquals(2800, (float) $payment->amount);
    }
}
