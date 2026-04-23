<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use LiqPay;

class StudentPaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student()->with('subscriptionTemplate')->first();

        abort_if(!$student, 404, 'Студента не знайдено.');

        return view('student.payments.index', [
            'student' => $student,
            'template' => $student->subscriptionTemplate,
        ]);
    }

    public function checkout(Request $request, Payment $payment)
    {
        $user = $request->user();
        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student;
        abort_if(!$student || $student->id !== $payment->student_id, 403);
        abort_if($payment->status !== 'pending', 422, 'Цей платіж уже оброблено.');

        $payload = $payment->payload ?? [];
        $templateId = $payload['subscription_template_id'] ?? null;
        abort_if(!$templateId, 422, 'Не знайдено шаблон абонемента.');

        $template = SubscriptionTemplate::findOrFail($templateId);

        $liqpay = new LiqPay(
            config('services.liqpay.public_key'),
            config('services.liqpay.private_key')
        );

        $params = [
            'version' => 3,
            'action' => 'pay',
            'amount' => $payment->amount,
            'currency' => 'UAH',
            'description' => $payment->description,
            'order_id' => $payment->provider_order_id,
            'language' => 'uk',
            'result_url' => route('student.payments.result'),
            'server_url' => 'https://geology-imitate-recent.ngrok-free.dev/liqpay/callback',
        ];

        if (config('services.liqpay.sandbox')) {
            $params['sandbox'] = '1';
        }

        $form = $liqpay->cnb_form($params);

        return view('student.payments.checkout', compact('payment', 'template', 'form'));
    }


    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user && $user->isStudent(), 403);

        $student = $user->student()->with('subscriptionTemplate')->first();

        abort_if(!$student, 404, 'Студента не знайдено.');
        abort_if(!$student->subscriptionTemplate, 422, 'Для вас не закріплено абонемент.');

        $template = $student->subscriptionTemplate;

        $payment = Payment::create([
            'student_id' => $student->id,
            'amount' => $template->price,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'subscription',
            'provider' => 'liqpay',
            'provider_order_id' => (string) Str::uuid(),
            'description' => 'Оплата абонемента: ' . $template->title,
            'payload' => [
                'subscription_template_id' => $template->id,
            ],
        ]);

        return redirect()->route('student.payments.checkout', $payment);
    }

    public function result(Request $request): RedirectResponse
    {
        return redirect()
            ->route('student.dashboard')
            ->with('success', 'Якщо оплата була успішною, вона скоро відобразиться в кабінеті.');
    }

}
