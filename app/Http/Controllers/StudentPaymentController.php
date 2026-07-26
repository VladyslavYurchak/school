<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Services\MonoPayPaymentProcessor;
use App\Services\MonoPayService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentPaymentController extends Controller
{
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

        if ($payment->hasMonoPayInvoice() && !$payment->hasReusableMonoPayInvoice()) {
            $payment->failExpiredMonoPayInvoice();

            return redirect()
                ->route('student.dashboard')
                ->with('error', 'Термін дії рахунку минув. Створіть новий платіж.');
        }

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
        $paymentDescription = $this->subscriptionPaymentDescription($startDate, $student);

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
            if ($existingPendingPayment->description !== $paymentDescription) {
                $existingPendingPayment->update([
                    'status' => 'failed',
                    'payload' => array_merge(
                        is_array($existingPendingPayment->payload) ? $existingPendingPayment->payload : [],
                        [
                            'description_changed_locally' => true,
                            'description_changed_locally_at' => now()->toISOString(),
                        ]
                    ),
                ]);
            } elseif ($existingPendingPayment->hasReusableMonoPayInvoice()) {
                return redirect()->route('student.payments.checkout', $existingPendingPayment);
            } else {
                $existingPendingPayment->failExpiredMonoPayInvoice();
            }

        }

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => $template->price,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => (string) Str::uuid(),
            'description' => $paymentDescription,
            'payload' => [
                'subscription_template_id' => $template->id,
                'subscription_month' => $data['subscription_month'],
            ],
        ]);

        return redirect()->route('student.payments.checkout', $payment);
    }

    public function result(
        Request $request,
        MonoPayService $monoPay,
        MonoPayPaymentProcessor $processor
    ): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student;
        $paymentId = $request->integer('payment');
        $payment = $paymentId && $student
            ? Payment::query()
                ->whereKey($paymentId)
                ->where('student_id', $student->id)
                ->first()
            : null;

        if (!$payment) {
            return redirect()
                ->route('student.dashboard')
                ->with('error', 'Не вдалося визначити платіж.');
        }

        if (
            in_array($payment->status, ['pending', 'failed'], true)
            && $payment->provider_payment_id
        ) {
            try {
                $invoice = $monoPay->getInvoiceStatus($payment->provider_payment_id);

                if (!$processor->invoiceMatchesPayment($payment, $invoice)) {
                    throw new \RuntimeException('MonoPay invoice does not match the local payment.');
                }

                $processor->process($payment, $invoice);
                $payment->refresh();
            } catch (\Throwable $e) {
                Log::error('MONOPAY RETURN STATUS CHECK ERROR', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $message = match ($payment->status) {
            'paid' => 'Оплату успішно зараховано.',
            'failed' => 'Оплату не завершено. Спробуйте створити новий платіж.',
            'refunded' => 'Платіж повернено.',
            default => 'Платіж ще обробляється. Статус оновиться автоматично.',
        };

        return redirect()
            ->route('student.dashboard')
            ->with($payment->status === 'paid' ? 'success' : 'error', $message);
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
                'label' => $this->subscriptionPeriodLabel($month),
            ];
        }

        return $months;
    }

    private function subscriptionPeriodLabel(Carbon $month): string
    {
        $months = [
            1 => 'січень',
            2 => 'лютий',
            3 => 'березень',
            4 => 'квітень',
            5 => 'травень',
            6 => 'червень',
            7 => 'липень',
            8 => 'серпень',
            9 => 'вересень',
            10 => 'жовтень',
            11 => 'листопад',
            12 => 'грудень',
        ];

        return $months[(int) $month->month] . ' ' . $month->format('Y');
    }

    private function subscriptionPaymentDescription(Carbon $month, $student): string
    {
        $studentName = trim(($student->first_name ?? '') . ' ' . ($student->last_name ?? ''));

        if ($studentName === '') {
            $studentName = $student->full_name;
        }

        return 'Оплата за навчання за період ' . $this->subscriptionPeriodLabel($month) . ' - ' . $studentName;
    }
}
