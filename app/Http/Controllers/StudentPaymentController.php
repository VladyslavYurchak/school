<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Services\MonoPayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentPaymentController extends Controller
{
    private const MONOPAY_INVOICE_VALIDITY_SECONDS = 3600;
    private const PAYMENT_MONTH_PAST_LIMIT = 2;
    private const PAYMENT_MONTH_FUTURE_LIMIT = 2;

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student()
            ->with(['subscriptionTemplate', 'subscriptions' => function ($query) {
                $query
                    ->where('type', 'subscription')
                    ->where('status', 'active');
            }])
            ->first();

        abort_if(!$student, 404, 'Student was not found.');

        return view('student.payments.index', [
            'student' => $student,
            'template' => $student->subscriptionTemplate,
            'defaultPaymentMonth' => $this->defaultPaymentMonth($student),
            'minPaymentMonth' => $this->minPaymentMonth(),
            'maxPaymentMonth' => $this->maxPaymentMonth(),
            'allowedPaymentMonths' => $this->allowedPaymentMonths(),
        ]);
    }

    public function checkout(Request $request, Payment $payment, MonoPayService $monoPay)
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student;

        abort_if(!$student || $student->id !== $payment->student_id, 403);
        abort_if($payment->status !== 'pending', 422, 'This payment has already been processed.');

        $payload = $payment->payload ?? [];

        $template = null;
        $course = null;
        $lesson = null;

        if ($templateId = ($payload['subscription_template_id'] ?? null)) {
            $template = SubscriptionTemplate::findOrFail($templateId);
        }

        if ($courseId = ($payload['course_id'] ?? null)) {
            $course = Course::findOrFail($courseId);
        }

        if ($lessonId = ($payload['lesson_id'] ?? null)) {
            $lesson = Lesson::findOrFail($lessonId);
        }

        abort_if(!$template && !$course && !$lesson, 422, 'Payment item was not found.');

        $existingMono = $payload['mono_invoice'] ?? null;

        if (
            $payment->provider_payment_id &&
            is_array($existingMono) &&
            !empty($existingMono['pageUrl'])
        ) {
            return redirect()->away($existingMono['pageUrl']);
        }

        $invoice = $monoPay->createInvoice($payment);

        $payment->update([
            'provider' => 'monopay',
            'provider_payment_id' => $invoice['invoiceId'] ?? null,
            'payload' => array_merge($payload, [
                'mono_invoice' => $invoice,
            ]),
        ]);

        return redirect()->away($invoice['pageUrl']);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $data = $request->validate([
            'subscription_month' => ['required', 'date_format:Y-m'],
        ]);

        if (!$this->isAllowedPaymentMonth($data['subscription_month'])) {
            return redirect()
                ->route('student.payments.index')
                ->withErrors([
                    'subscription_month' => 'Оберіть місяць у доступному діапазоні.',
                ]);
        }

        $student = $user->student()->with('subscriptionTemplate')->first();

        abort_if(!$student, 404, 'Student was not found.');
        abort_if(!$student->subscriptionTemplate, 422, 'No subscription is assigned to you.');

        $template = $student->subscriptionTemplate;
        $startDate = Carbon::createFromFormat('Y-m', $data['subscription_month'])->startOfMonth();
        $endDate = (clone $startDate)->endOfMonth();

        $hasActiveSubscription = StudentSubscription::query()
            ->where('student_id', $student->id)
            ->where('type', 'subscription')
            ->where('status', 'active')
            ->whereDate('start_date', $startDate->toDateString())
            ->whereDate('end_date', $endDate->toDateString())
            ->exists();

        if ($hasActiveSubscription) {
            return redirect()
                ->route('student.payments.index')
                ->with('error', 'Цей місяць вже оплачено.');
        }

        $existingPendingPayment = Payment::query()
            ->where('student_id', $student->id)
            ->where('status', 'pending')
            ->where('type', 'subscription')
            ->where('provider', 'monopay')
            ->where('payload->subscription_template_id', $template->id)
            ->where('payload->subscription_month', $data['subscription_month'])
            ->latest()
            ->first();

        if ($existingPendingPayment) {
            $payload = is_array($existingPendingPayment->payload) ? $existingPendingPayment->payload : [];
            $hasInvoice = $existingPendingPayment->provider_payment_id
                || !empty($payload['mono_invoice']['invoiceId'])
                || !empty($payload['mono_invoice']['pageUrl']);

            $invoiceIsFresh = !$hasInvoice
                || $existingPendingPayment->updated_at->gt(now()->subSeconds(self::MONOPAY_INVOICE_VALIDITY_SECONDS));

            if ($invoiceIsFresh) {
                return redirect()->route('student.payments.checkout', $existingPendingPayment);
            }

            $existingPendingPayment->update([
                'status' => 'failed',
                'payload' => array_merge($payload, [
                    'expired_locally' => true,
                    'expired_locally_at' => now()->toISOString(),
                ]),
            ]);
        }

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => $template->price,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => (string) Str::uuid(),
            'description' => 'Оплата абонемента: ' . $template->title,
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => $data['subscription_month'],
            ],
        ]);

        return redirect()->route('student.payments.checkout', $payment);
    }

    public function result(Request $request): RedirectResponse
    {
        return redirect()
            ->route('student.dashboard')
            ->with('success', 'Якщо оплата пройшла успішно, вона скоро з’явиться у вашому кабінеті.');
    }

    private function defaultPaymentMonth($student): string
    {
        $paidMonths = $student->subscriptions
            ->map(fn (StudentSubscription $subscription) => $subscription->start_date->format('Y-m'))
            ->all();

        $month = now()->startOfMonth();

        $monthOffsets = array_merge(
            range(0, self::PAYMENT_MONTH_FUTURE_LIMIT),
            range(-1, -self::PAYMENT_MONTH_PAST_LIMIT)
        );

        foreach ($monthOffsets as $offset) {
            $monthValue = $month->copy()->addMonths($offset)->format('Y-m');

            if (!in_array($monthValue, $paidMonths, true)) {
                return $monthValue;
            }
        }

        return $this->maxPaymentMonth();
    }

    private function isAllowedPaymentMonth(string $month): bool
    {
        $selected = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $min = now()->startOfMonth()->subMonths(self::PAYMENT_MONTH_PAST_LIMIT);
        $max = now()->startOfMonth()->addMonths(self::PAYMENT_MONTH_FUTURE_LIMIT);

        return $selected->betweenIncluded($min, $max);
    }

    private function minPaymentMonth(): string
    {
        return now()->startOfMonth()->subMonths(self::PAYMENT_MONTH_PAST_LIMIT)->format('Y-m');
    }

    private function maxPaymentMonth(): string
    {
        return now()->startOfMonth()->addMonths(self::PAYMENT_MONTH_FUTURE_LIMIT)->format('Y-m');
    }

    private function allowedPaymentMonths(): array
    {
        $months = [];
        $start = now()->startOfMonth()->subMonths(self::PAYMENT_MONTH_PAST_LIMIT);
        $end = now()->startOfMonth()->addMonths(self::PAYMENT_MONTH_FUTURE_LIMIT);

        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $months[] = [
                'value' => $month->format('Y-m'),
                'label' => $month->translatedFormat('F Y'),
            ];
        }

        return $months;
    }
}
