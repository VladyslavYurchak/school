<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\User;
use App\Services\MonoPayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonoPayWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_subscription_webhook_creates_active_subscription_for_selected_month_once(): void
    {
        Carbon::setTestNow('2026-06-14 12:00:00');

        $template = SubscriptionTemplate::factory()->create([
            'type' => 'individual',
            'lessons_per_week' => 2,
            'price' => 3200,
        ]);

        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'subscription_id' => $template->id,
        ]);

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 3200,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'order-123',
            'description' => 'Subscription payment',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
        ]);

        $payload = [
            'invoiceId' => 'invoice-123',
            'reference' => $payment->provider_order_id,
            'status' => 'success',
        ];

        $this->postSignedMonoWebhook($payload)->assertOk();
        $this->postSignedMonoWebhook($payload, publicKeyFormat: 'pem')->assertOk();

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertSame('invoice-123', $payment->provider_payment_id);

        $this->assertDatabaseCount('student_subscriptions', 1);
        $this->assertDatabaseHas('student_subscriptions', [
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'payment_id' => $payment->id,
            'type' => 'subscription',
            'status' => 'active',
            'price' => 3200,
            'lessons_total' => 8,
            'lessons_used' => 0,
        ]);

        $subscription = StudentSubscription::firstOrFail();

        $this->assertSame('2026-08-01', $subscription->start_date->toDateString());
        $this->assertSame('2026-08-31', $subscription->end_date->toDateString());

        $this->postSignedMonoWebhook([
            'invoiceId' => 'invoice-123',
            'reference' => $payment->provider_order_id,
            'status' => 'expired',
        ])->assertOk();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('active', $subscription->fresh()->status);

        $this->postSignedMonoWebhook([
            'invoiceId' => 'invoice-123',
            'reference' => $payment->provider_order_id,
            'status' => 'reversed',
        ])->assertOk();

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('cancelled', $subscription->fresh()->status);

        $this->postSignedMonoWebhook($payload)->assertOk();

        $this->assertSame('refunded', $payment->fresh()->status);
        $this->assertSame('cancelled', $subscription->fresh()->status);
    }

    public function test_failed_subscription_webhook_marks_payment_failed_without_creating_subscription(): void
    {
        $template = SubscriptionTemplate::factory()->create();
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'subscription_id' => $template->id,
        ]);

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2500,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'order-failed',
            'description' => 'Subscription payment',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
        ]);

        $this->postSignedMonoWebhook([
            'invoiceId' => 'invoice-failed',
            'reference' => $payment->provider_order_id,
            'status' => 'failure',
        ])->assertOk();

        $payment->refresh();

        $this->assertSame('failed', $payment->status);
        $this->assertSame('invoice-failed', $payment->provider_payment_id);
        $this->assertDatabaseCount('student_subscriptions', 0);

    }

    public function test_successful_subscription_webhook_falls_back_to_invoice_status_when_signature_cannot_be_verified(): void
    {
        Carbon::setTestNow('2026-06-14 12:00:00');

        $template = SubscriptionTemplate::factory()->create([
            'type' => 'individual',
            'lessons_per_week' => 2,
            'price' => 5,
        ]);

        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'subscription_id' => $template->id,
        ]);

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 5,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'order-fallback',
            'provider_payment_id' => 'invoice-fallback',
            'description' => 'Subscription payment',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-06',
            ],
        ]);

        $payload = [
            'invoiceId' => 'invoice-fallback',
            'reference' => 'order-fallback',
            'status' => 'success',
            'amount' => 500,
            'ccy' => 980,
        ];

        $this->mock(MonoPayService::class, function ($mock) use ($payload) {
            $mock->shouldReceive('getPublicKey')
                ->once()
                ->andReturn('not-a-valid-public-key');

            $mock->shouldReceive('getInvoiceStatus')
                ->once()
                ->with('invoice-fallback')
                ->andReturn($payload);
        });

        $this->call(
            'POST',
            route('monopay.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SIGN' => base64_encode('bad-signature'),
            ],
            json_encode($payload, JSON_UNESCAPED_SLASHES)
        )->assertOk();

        $payment->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertDatabaseHas('student_subscriptions', [
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'payment_id' => $payment->id,
            'status' => 'active',
            'price' => 5,
        ]);
    }

    public function test_successful_course_and_lesson_webhooks_grant_student_access_without_subscription(): void
    {
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
        ]);

        $course = Course::query()->create([
            'title' => 'Paid course',
            'description' => 'Course',
            'language_id' => Language::query()->create(['name' => 'English'])->id,
            'price' => 900,
            'is_published' => true,
        ]);

        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'title' => 'Paid lesson',
            'description' => 'Lesson',
            'content' => 'Content',
            'lesson_type' => 'text',
            'position' => 1,
            'price' => 300,
            'is_published' => true,
        ]);

        $coursePayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 900,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'course-order',
            'payload' => [
                'course_id' => $course->id,
                'user_id' => $student->user_id,
            ],
        ]);

        $lessonPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 300,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'lesson-order',
            'payload' => [
                'lesson_id' => $lesson->id,
                'user_id' => $student->user_id,
            ],
        ]);

        $competingCoursePayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 900,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'competing-course-order',
            'payload' => [
                'course_id' => $course->id,
                'user_id' => $student->user_id,
            ],
        ]);

        $this->postSignedMonoWebhook([
            'invoiceId' => 'course-invoice',
            'reference' => $coursePayment->provider_order_id,
            'status' => 'success',
        ])->assertOk();

        $this->postSignedMonoWebhook([
            'invoiceId' => 'lesson-invoice',
            'reference' => $lessonPayment->provider_order_id,
            'status' => 'success',
        ])->assertOk();

        $this->assertDatabaseHas('user_course', [
            'user_id' => $student->user_id,
            'course_id' => $course->id,
            'status' => 'paid',
            'paid_amount' => 900,
        ]);
        $this->assertSame('failed', $competingCoursePayment->fresh()->status);
        $this->assertSame(
            $coursePayment->id,
            $competingCoursePayment->fresh()->payload['superseded_by_paid_payment_id']
        );

        $this->assertDatabaseHas('user_lesson', [
            'user_id' => $student->user_id,
            'lesson_id' => $lesson->id,
            'status' => 'paid',
            'paid_amount' => 300,
        ]);

        $this->assertDatabaseCount('student_subscriptions', 0);

        $this->postSignedMonoWebhook([
            'invoiceId' => 'course-invoice',
            'reference' => $coursePayment->provider_order_id,
            'status' => 'reversed',
        ])->assertOk();

        $this->postSignedMonoWebhook([
            'invoiceId' => 'lesson-invoice',
            'reference' => $lessonPayment->provider_order_id,
            'status' => 'reversed',
        ])->assertOk();

        $this->assertDatabaseHas('user_course', [
            'user_id' => $student->user_id,
            'course_id' => $course->id,
            'status' => 'refunded',
        ]);

        $this->assertDatabaseHas('user_lesson', [
            'user_id' => $student->user_id,
            'lesson_id' => $lesson->id,
            'status' => 'refunded',
        ]);
    }

    public function test_webhook_rejects_amount_mismatch_without_crediting_payment(): void
    {
        $template = SubscriptionTemplate::factory()->create(['price' => 2500]);
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'subscription_id' => $template->id,
        ]);
        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2500,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_payment_id' => 'amount-invoice',
            'provider_order_id' => 'amount-order',
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
        ]);

        $this->postSignedMonoWebhook([
            'invoiceId' => 'amount-invoice',
            'reference' => 'amount-order',
            'status' => 'success',
            'amount' => 1,
            'ccy' => 980,
        ])->assertBadRequest();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('student_subscriptions', 0);
    }

    public function test_webhook_keeps_payment_pending_when_purchased_item_cannot_be_fulfilled(): void
    {
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
        ]);
        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => 500,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_payment_id' => 'missing-course-invoice',
            'provider_order_id' => 'missing-course-order',
            'payload' => [
                'course_id' => 999999,
                'user_id' => $student->user_id,
            ],
        ]);

        $this->postSignedMonoWebhook([
            'invoiceId' => 'missing-course-invoice',
            'reference' => 'missing-course-order',
            'status' => 'success',
            'amount' => 50000,
            'ccy' => 980,
        ])->assertStatus(409);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertDatabaseCount('user_course', 0);
    }

    public function test_reconcile_command_restores_and_links_missing_paid_subscriptions(): void
    {
        $template = SubscriptionTemplate::factory()->create([
            'lessons_per_week' => 2,
            'price' => 2500,
        ]);
        $student = Student::factory()->create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'subscription_id' => $template->id,
        ]);
        $missingPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2500,
            'currency' => 'UAH',
            'status' => 'paid',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'missing-fulfillment-order',
            'paid_at' => now(),
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-08',
            ],
        ]);
        $unlinkedPayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 2500,
            'currency' => 'UAH',
            'status' => 'paid',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'unlinked-fulfillment-order',
            'paid_at' => now(),
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => '2026-09',
            ],
        ]);
        $unlinkedSubscription = StudentSubscription::create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'payment_id' => null,
            'price' => 2500,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'lessons_total' => 8,
            'paid_at' => now(),
        ]);

        $this->artisan('payments:reconcile-paid')
            ->expectsOutputToContain("Payment {$missingPayment->id}: missing")
            ->expectsOutputToContain("Payment {$unlinkedPayment->id}: missing_link")
            ->assertSuccessful();

        $this->assertDatabaseMissing('student_subscriptions', [
            'payment_id' => $missingPayment->id,
        ]);
        $this->assertNull($unlinkedSubscription->fresh()->payment_id);

        $this->artisan('payments:reconcile-paid', ['--apply' => true])
            ->expectsOutputToContain('Problems: 2; repaired: 2.')
            ->assertSuccessful();

        $this->assertDatabaseHas('student_subscriptions', [
            'student_id' => $student->id,
            'payment_id' => $missingPayment->id,
            'start_date' => '2026-08-01 00:00:00',
            'end_date' => '2026-08-31 00:00:00',
        ]);
        $this->assertSame($unlinkedPayment->id, $unlinkedSubscription->fresh()->payment_id);
    }

    private function postSignedMonoWebhook(array $payload, string $publicKeyFormat = 'base64')
    {
        $options = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        foreach ([
            getenv('OPENSSL_CONF') ?: null,
            'C:\\Users\\vlad\\.config\\herd\\openssl.cnf',
            'C:\\Program Files\\Git\\mingw64\\etc\\ssl\\openssl.cnf',
            'C:\\Program Files\\Git\\usr\\ssl\\openssl.cnf',
        ] as $configPath) {
            if ($configPath && file_exists($configPath)) {
                $options['config'] = $configPath;
                break;
            }
        }

        $keyPair = openssl_pkey_new($options);

        $details = openssl_pkey_get_details($keyPair);
        $publicKeyBase64 = preg_replace('/-----.*?-----|\s/', '', $details['key']);

        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        openssl_sign($rawBody, $signature, $keyPair, OPENSSL_ALGO_SHA256);

        $publicKey = $publicKeyFormat === 'pem'
            ? $details['key']
            : $publicKeyBase64;

        $this->mock(MonoPayService::class, function ($mock) use ($publicKey) {
            $mock->shouldReceive('getPublicKey')
                ->once()
                ->andReturn($publicKey);
        });

        return $this->call(
            'POST',
            route('monopay.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_SIGN' => base64_encode($signature),
            ],
            $rawBody
        );
    }
}
