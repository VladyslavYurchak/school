<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_creates_pending_monopay_payment_for_assigned_subscription_month(): void
    {
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
}
