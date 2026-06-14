<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\SubscriptionTemplate;
use App\Services\MonoPayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudentPaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student()->with('subscriptionTemplate')->first();

        abort_if(!$student, 404, 'Student was not found.');

        return view('student.payments.index', [
            'student' => $student,
            'template' => $student->subscriptionTemplate,
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

        $student = $user->student()->with('subscriptionTemplate')->first();

        abort_if(!$student, 404, 'Student was not found.');
        abort_if(!$student->subscriptionTemplate, 422, 'No subscription is assigned to you.');

        $template = $student->subscriptionTemplate;

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
}
