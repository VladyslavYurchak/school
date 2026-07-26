<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MonoPayPaymentProcessor
{
    public function invoiceMatchesPayment(Payment $payment, array $invoice): bool
    {
        $invoiceId = $invoice['invoiceId'] ?? null;
        $reference = $invoice['reference'] ?? null;
        $amount = isset($invoice['amount']) ? (int) $invoice['amount'] : null;
        $currency = isset($invoice['ccy']) ? (int) $invoice['ccy'] : null;
        $paymentAmount = (int) round(((float) $payment->amount) * 100);

        if (!$invoiceId) {
            return false;
        }

        if ($payment->provider_payment_id && $invoiceId !== $payment->provider_payment_id) {
            return false;
        }

        if ($reference && $reference !== $payment->provider_order_id) {
            return false;
        }

        if ($amount !== null && $amount !== $paymentAmount) {
            return false;
        }

        return $currency === null || $currency === 980;
    }

    public function process(Payment $payment, array $monoPayload): void
    {
        $status = $monoPayload['status'] ?? null;

        if ($status === 'success') {
            $this->markPaidAndFulfill($payment, $monoPayload);

            return;
        }

        if (in_array($status, ['failure', 'expired'], true)) {
            $this->markFailed($payment, $monoPayload);

            return;
        }

        if ($status === 'reversed') {
            $this->markRefundedAndRevoke($payment, $monoPayload);
        }
    }

    private function markPaidAndFulfill(Payment $payment, array $monoPayload): void
    {
        DB::transaction(function () use ($payment, $monoPayload) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if (in_array($lockedPayment->status, ['paid', 'refunded'], true)) {
                return;
            }

            $payload = $this->mergedPayload($lockedPayment, $monoPayload);

            $this->fulfill($lockedPayment, $payload);
            $this->failCompetingPendingPayments($lockedPayment, $payload);

            $lockedPayment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'provider_payment_id' => $monoPayload['invoiceId'] ?? $lockedPayment->provider_payment_id,
                'payload' => $payload,
            ]);
        });
    }

    private function markFailed(Payment $payment, array $monoPayload): void
    {
        DB::transaction(function () use ($payment, $monoPayload) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status !== 'pending') {
                return;
            }

            $lockedPayment->update([
                'status' => 'failed',
                'provider_payment_id' => $monoPayload['invoiceId'] ?? $lockedPayment->provider_payment_id,
                'payload' => $this->mergedPayload($lockedPayment, $monoPayload),
            ]);
        });
    }

    private function markRefundedAndRevoke(Payment $payment, array $monoPayload): void
    {
        DB::transaction(function () use ($payment, $monoPayload) {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'refunded') {
                return;
            }

            $payload = $this->mergedPayload($lockedPayment, $monoPayload);
            $student = $lockedPayment->student()->with('user')->first();

            if ($student?->user && ($courseId = ($payload['course_id'] ?? null))) {
                $student->user->courses()->updateExistingPivot($courseId, [
                    'status' => 'refunded',
                ]);
            }

            if ($student?->user && ($lessonId = ($payload['lesson_id'] ?? null))) {
                $student->user->lessons()->updateExistingPivot($lessonId, [
                    'status' => 'refunded',
                ]);
            }

            $lockedPayment->subscriptions()
                ->whereIn('status', ['pending', 'active'])
                ->update(['status' => 'cancelled']);

            $lockedPayment->update([
                'status' => 'refunded',
                'provider_payment_id' => $monoPayload['invoiceId'] ?? $lockedPayment->provider_payment_id,
                'payload' => $payload,
            ]);
        });
    }

    private function fulfill(Payment $payment, array $payload): void
    {
        if ($courseId = ($payload['course_id'] ?? null)) {
            $this->fulfillCourse($payment, (int) $courseId);

            return;
        }

        if ($lessonId = ($payload['lesson_id'] ?? null)) {
            $this->fulfillLesson($payment, (int) $lessonId);

            return;
        }

        $this->fulfillSubscription($payment, $payload);
    }

    private function fulfillCourse(Payment $payment, int $courseId): void
    {
        if ($payment->type !== 'single' || !Course::query()->whereKey($courseId)->exists()) {
            throw new RuntimeException('The paid course could not be fulfilled.');
        }

        $student = $payment->student()->with('user')->first();

        if (!$student?->user) {
            throw new RuntimeException('The student account for the course payment was not found.');
        }

        $student->user->courses()->syncWithoutDetaching([
            $courseId => [
                'status' => 'paid',
                'paid_amount' => $payment->amount,
            ],
        ]);
    }

    private function fulfillLesson(Payment $payment, int $lessonId): void
    {
        if ($payment->type !== 'single' || !Lesson::query()->whereKey($lessonId)->exists()) {
            throw new RuntimeException('The paid lesson could not be fulfilled.');
        }

        $student = $payment->student()->with('user')->first();

        if (!$student?->user) {
            throw new RuntimeException('The student account for the lesson payment was not found.');
        }

        $student->user->lessons()->syncWithoutDetaching([
            $lessonId => [
                'status' => 'paid',
                'paid_amount' => $payment->amount,
            ],
        ]);
    }

    private function fulfillSubscription(Payment $payment, array $payload): void
    {
        if ($payment->type !== 'subscription') {
            throw new RuntimeException('The payment does not contain a purchasable item.');
        }

        $templateId = $payload['subscription_template_id'] ?? null;
        $subscriptionMonth = $payload['subscription_month'] ?? null;
        $template = $templateId ? SubscriptionTemplate::query()->find($templateId) : null;

        if (!$template || !$subscriptionMonth) {
            throw new RuntimeException('The subscription payment could not be fulfilled.');
        }

        try {
            $startDate = Carbon::createFromFormat('!Y-m', $subscriptionMonth)->startOfMonth();
        } catch (\Throwable) {
            throw new RuntimeException('The subscription month is invalid.');
        }

        $endDate = $startDate->copy()->endOfMonth();

        $subscription = StudentSubscription::query()
            ->where('student_id', $payment->student_id)
            ->where('type', 'subscription')
            ->whereDate('start_date', $startDate->toDateString())
            ->whereDate('end_date', $endDate->toDateString())
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if ($subscription) {
            return;
        }

        StudentSubscription::create([
            'student_id' => $payment->student_id,
            'subscription_template_id' => $template->id,
            'payment_id' => $payment->id,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'price' => $payment->amount,
            'type' => 'subscription',
            'status' => 'active',
            'lessons_total' => $template->lessons_per_week * 4,
            'lessons_used' => 0,
            'paid_at' => now(),
        ]);
    }

    private function mergedPayload(Payment $payment, array $monoPayload): array
    {
        $payload = is_array($payment->payload) ? $payment->payload : [];

        return array_merge($payload, [
            'mono_webhook' => $monoPayload,
        ]);
    }

    private function failCompetingPendingPayments(Payment $payment, array $payload): void
    {
        $query = Payment::query()
            ->where('student_id', $payment->student_id)
            ->where('status', 'pending')
            ->whereKeyNot($payment->id);

        if ($courseId = ($payload['course_id'] ?? null)) {
            $query->where('payload->course_id', $courseId);
        } elseif ($lessonId = ($payload['lesson_id'] ?? null)) {
            $query->where('payload->lesson_id', $lessonId);
        } elseif (
            ($templateId = ($payload['subscription_template_id'] ?? null))
            && ($month = ($payload['subscription_month'] ?? null))
        ) {
            $query
                ->where('payload->subscription_template_id', $templateId)
                ->where('payload->subscription_month', $month);
        } else {
            return;
        }

        $query->get()->each(function (Payment $competingPayment) use ($payment) {
            $competingPayload = is_array($competingPayment->payload)
                ? $competingPayment->payload
                : [];

            $competingPayment->update([
                'status' => 'failed',
                'payload' => array_merge($competingPayload, [
                    'superseded_by_paid_payment_id' => $payment->id,
                    'superseded_at' => now()->toISOString(),
                ]),
            ]);
        });
    }
}
