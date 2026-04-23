<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LiqPayCallbackController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Log::info('LIQPAY CALLBACK RAW', $request->all());

        $data = $request->input('data');
        $signature = $request->input('signature');

        if (!$data || !$signature) {
            Log::warning('LIQPAY: missing data or signature');
            return response('bad request', 400);
        }

        $privateKey = config('services.liqpay.private_key');

        $expectedSignature = base64_encode(
            sha1($privateKey . $data . $privateKey, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('LIQPAY: invalid signature', [
                'expected' => $expectedSignature,
                'received' => $signature,
            ]);

            return response('invalid signature', 400);
        }

        $payload = json_decode(base64_decode($data), true);

        Log::info('LIQPAY PAYLOAD DECODED', $payload ?? []);

        if (!is_array($payload)) {
            Log::warning('LIQPAY: invalid payload');
            return response('invalid payload', 400);
        }

        $orderId = $payload['order_id'] ?? null;
        $status = $payload['status'] ?? null;
        $liqpayPaymentId = $payload['payment_id'] ?? null;

        if (!$orderId) {
            Log::warning('LIQPAY: missing order_id');
            return response('missing order id', 400);
        }

        $payment = Payment::where('provider_order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('LIQPAY: payment not found', [
                'order_id' => $orderId,
            ]);

            return response('payment not found', 404);
        }

        // Оновлюємо технічні дані платежу в будь-якому разі
        $payment->update([
            'provider_payment_id' => $liqpayPaymentId,
            'payload' => $payload,
        ]);

        // Успішні стани
        if (in_array($status, ['success', 'sandbox'], true)) {
            // Якщо вже paid — просто повертаємо ok, щоб не дублювати логіку
            if ($payment->status === 'paid') {
                return response('ok');
            }

            DB::transaction(function () use ($payment, $payload) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payload' => $payload,
                ]);

                $paymentPayload = is_array($payment->payload) ? $payment->payload : [];
                $templateId = $paymentPayload['subscription_template_id'] ?? null;

                if (!$templateId) {
                    Log::warning('LIQPAY: subscription_template_id not found in payment payload', [
                        'payment_id' => $payment->id,
                    ]);
                    return;
                }

                $template = SubscriptionTemplate::find($templateId);

                if (!$template) {
                    Log::warning('LIQPAY: template not found', [
                        'template_id' => $templateId,
                        'payment_id' => $payment->id,
                    ]);
                    return;
                }

                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();

                $exists = StudentSubscription::query()
                    ->where('student_id', $payment->student_id)
                    ->where('type', 'subscription')
                    ->where('start_date', $startDate->toDateString())
                    ->where('end_date', $endDate->toDateString())
                    ->whereIn('status', ['pending', 'active'])
                    ->exists();

                if (!$exists) {
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
            });

            return response('ok');
        }

        // Неуспішні стани
        if (in_array($status, ['failure', 'error'], true)) {
            $payment->update([
                'status' => 'failed',
                'payload' => $payload,
            ]);

            return response('ok');
        }

        // Інші проміжні стани просто логнемо
        Log::info('LIQPAY: unhandled status', [
            'status' => $status,
            'payment_id' => $payment->id,
        ]);

        return response('ok');
    }
}
