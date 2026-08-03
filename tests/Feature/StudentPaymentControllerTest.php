<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\User;
use App\Services\MonoPayService;
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
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
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
            ->assertDontSee('value="2026-08"', false)
            ->assertSee('value="2026-09" selected', false)
            ->assertSee('вересень 2026')
            ->assertSee('Ви можете оплатити поточний місяць')
            ->assertDontSee('name="subscription_template_id"', false);
    }

    public function test_payment_page_hides_every_active_paid_month_in_the_allowed_window(): void
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

        foreach (['2026-06', '2026-08', '2026-10'] as $month) {
            $start = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();

            StudentSubscription::factory()->create([
                'student_id' => $student->id,
                'subscription_template_id' => $template->id,
                'type' => 'subscription',
                'status' => 'active',
                'start_date' => $start,
                'end_date' => $start->copy()->endOfMonth(),
            ]);
        }

        $this
            ->actingAs($user)
            ->get(route('student.payments.index'))
            ->assertOk()
            ->assertDontSee('value="2026-06"', false)
            ->assertSee('value="2026-07"', false)
            ->assertDontSee('value="2026-08"', false)
            ->assertSee('value="2026-09" selected', false)
            ->assertDontSee('value="2026-10"', false);
    }

    public function test_cancelled_subscription_month_remains_available_for_payment(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create();
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'cancelled',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-31',
        ]);

        $this
            ->actingAs($user)
            ->get(route('student.payments.index'))
            ->assertOk()
            ->assertSee('value="2026-08" selected', false);
    }

    public function test_expired_but_paid_subscription_month_is_hidden_and_cannot_be_paid_again(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create();
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'expired',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'paid_at' => '2026-07-01 10:00:00',
        ]);

        $this
            ->actingAs($user)
            ->get(route('student.payments.index'))
            ->assertOk()
            ->assertDontSee('value="2026-07"', false);

        $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-07',
            ])
            ->assertRedirect(route('student.payments.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_payment_page_handles_all_five_months_already_paid(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create();
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        foreach (range(-2, 2) as $offset) {
            $start = now()->startOfMonth()->addMonths($offset);

            StudentSubscription::factory()->create([
                'student_id' => $student->id,
                'subscription_template_id' => $template->id,
                'type' => 'subscription',
                'status' => 'active',
                'start_date' => $start,
                'end_date' => $start->copy()->endOfMonth(),
            ]);
        }

        $this
            ->actingAs($user)
            ->get(route('student.payments.index'))
            ->assertOk()
            ->assertSee('Усі доступні місяці вже оплачені.')
            ->assertDontSee('name="subscription_month"', false)
            ->assertSee('disabled', false);
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
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
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
        $this->assertSame('Оплата за навчання за період серпень 2026 - Ivan Petrenko', $payment->description);
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
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
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
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
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
            'description' => 'Оплата за навчання за період серпень 2026 - Ivan Petrenko',
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
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
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
            'description' => 'Оплата за навчання за період серпень 2026 - Ivan Petrenko',
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

    public function test_student_replaces_pending_subscription_payment_when_description_changed(): void
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
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
        ]);

        $oldPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'old-description-order',
            'provider_payment_id' => 'old-description-invoice',
            'description' => 'Оплата за навчання за період August 2026',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
                'mono_invoice' => [
                    'invoiceId' => 'old-description-invoice',
                    'pageUrl' => 'https://example.test/old-description-invoice',
                ],
            ],
        ]);

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
        $this->assertTrue($oldPayment->payload['description_changed_locally']);
        $this->assertSame('pending', $newPayment->status);
        $this->assertSame('Оплата за навчання за період серпень 2026 - Ivan Petrenko', $newPayment->description);
    }

    public function test_student_replaces_pending_subscription_payment_when_price_changed(): void
    {
        Carbon::setTestNow('2026-08-15 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 3200,
            'type' => 'individual',
        ]);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
        ]);
        $oldPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'old-price-order',
            'description' => 'Оплата за навчання за період серпень 2026 - Ivan Petrenko',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('student.payments.store'), [
                'subscription_month' => '2026-08',
            ]);

        $newPayment = Payment::query()
            ->whereKeyNot($oldPayment->id)
            ->firstOrFail();

        $response->assertRedirect(route('student.payments.checkout', $newPayment));
        $this->assertSame('failed', $oldPayment->fresh()->status);
        $this->assertTrue($oldPayment->fresh()->payload['amount_changed_locally']);
        $this->assertSame('pending', $newPayment->status);
        $this->assertEquals(3200, (float) $newPayment->amount);
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
            'first_name' => 'Ivan',
            'last_name' => 'Petrenko',
        ]);

        $existingPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'no-invoice-order',
            'description' => 'Оплата за навчання за період серпень 2026 - Ivan Petrenko',
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

    public function test_return_from_monopay_recovers_subscription_when_webhook_was_not_delivered(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'price' => 2800,
            'lessons_per_week' => 2,
            'type' => 'individual',
        ]);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);
        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_payment_id' => 'subscription-invoice',
            'provider_order_id' => 'subscription-order',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
        ]);

        $this->mock(MonoPayService::class, function ($mock) {
            $mock->shouldReceive('getInvoiceStatus')
                ->once()
                ->with('subscription-invoice')
                ->andReturn([
                    'invoiceId' => 'subscription-invoice',
                    'reference' => 'subscription-order',
                    'status' => 'success',
                    'amount' => 280000,
                    'ccy' => 980,
                ]);
        });

        $this->actingAs($user)
            ->get(route('student.payments.result', ['payment' => $payment->id]))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('success', 'Оплату успішно зараховано.')
            ->assertSessionHas('analytics_event.name', 'purchase')
            ->assertSessionHas('analytics_event.parameters.transaction_id', 'payment-'.$payment->id)
            ->assertSessionHas('analytics_event.parameters.value', 2800.0)
            ->assertSessionHas('analytics_event.parameters.currency', 'UAH');

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseHas('student_subscriptions', [
            'student_id' => $student->id,
            'payment_id' => $payment->id,
            'subscription_template_id' => $template->id,
            'status' => 'active',
        ]);
    }

    public function test_return_from_monopay_recovers_course_access_when_webhook_was_not_delivered(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $student = Student::factory()->create(['user_id' => $user->id]);
        $language = Language::create(['name' => 'English']);
        $course = Course::create([
            'title' => 'Paid course',
            'description' => 'Course',
            'language_id' => $language->id,
            'price' => 900,
            'is_published' => true,
        ]);
        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 900,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_payment_id' => 'course-invoice',
            'provider_order_id' => 'course-order',
            'payload' => [
                'course_id' => $course->id,
                'user_id' => $user->id,
            ],
        ]);

        $this->mock(MonoPayService::class, function ($mock) {
            $mock->shouldReceive('getInvoiceStatus')
                ->once()
                ->with('course-invoice')
                ->andReturn([
                    'invoiceId' => 'course-invoice',
                    'reference' => 'course-order',
                    'status' => 'success',
                    'amount' => 90000,
                    'ccy' => 980,
                ]);
        });

        $this->actingAs($user)
            ->get(route('student.payments.result', ['payment' => $payment->id]))
            ->assertRedirect(route('student.dashboard'));

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseHas('user_course', [
            'user_id' => $user->id,
            'course_id' => $course->id,
            'status' => 'paid',
            'paid_amount' => 900,
        ]);
    }

    public function test_return_from_monopay_does_not_credit_mismatched_invoice(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create(['price' => 2800]);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);
        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2800,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_payment_id' => 'invoice-mismatch',
            'provider_order_id' => 'expected-order',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
        ]);

        $this->mock(MonoPayService::class, function ($mock) {
            $mock->shouldReceive('getInvoiceStatus')
                ->once()
                ->andReturn([
                    'invoiceId' => 'invoice-mismatch',
                    'reference' => 'another-order',
                    'status' => 'success',
                    'amount' => 280000,
                    'ccy' => 980,
                ]);
        });

        $this->actingAs($user)
            ->get(route('student.payments.result', ['payment' => $payment->id]))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error');

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('student_subscriptions', 0);
    }

    public function test_student_cannot_reconcile_another_students_payment(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Student::factory()->create(['user_id' => $user->id]);

        $otherStudent = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
        ]);
        $payment = Payment::create([
            'student_id' => $otherStudent->id,
            'amount' => 100,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_payment_id' => 'other-invoice',
            'provider_order_id' => 'other-order',
        ]);

        $this->mock(MonoPayService::class, function ($mock) {
            $mock->shouldNotReceive('getInvoiceStatus');
        });

        $this->actingAs($user)
            ->get(route('student.payments.result', ['payment' => $payment->id]))
            ->assertRedirect(route('student.dashboard'))
            ->assertSessionHas('error', 'Не вдалося визначити платіж.');

        $this->assertSame('pending', $payment->fresh()->status);
    }
}
