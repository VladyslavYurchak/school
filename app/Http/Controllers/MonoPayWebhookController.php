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
        $expectedSignature = base64_encode(sha1($privateKey . $data . $privateKey, true));

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('LIQPAY: invalid signature');
            return response('invalid signature', 400);
        }

        $liqpayPayload = json_decode(base64_decode($data), true);

        Log::info('LIQPAY PAYLOAD DECODED', $liqpayPayload ?? []);

        if (!is_array($liqpayPayload)) {
            Log::warning('LIQPAY: invalid payload');
            return response('invalid payload', 400);
        }

        $orderId = $liqpayPayload['order_id'] ?? null;
        $status = $liqpayPayload['status'] ?? null;
        $liqpayPaymentId = $liqpayPayload['payment_id'] ?? null;

        if (!$orderId) {
            Log::warning('LIQPAY: missing order_id');
            return response('missing order id', 400);
        }

        $payment = Payment::where('provider_order_id', $orderId)->first();

        if (!$payment) {
            Log::warning('LIQPAY: payment not found', ['order_id' => $orderId]);
            return response('payment not found', 404);
        }

        $paymentPayload = is_array($payment->payload) ? $payment->payload : [];
        $mergedPayload = array_merge($paymentPayload, ['liqpay' => $liqpayPayload]);

        $payment->update([
            'provider_payment_id' => $liqpayPaymentId,
            'payload' => $mergedPayload,
        ]);

        if (in_array($status, ['success', 'sandbox'], true)) {
            if ($payment->status === 'paid') {
                return response('ok');
            }

            DB::transaction(function () use ($payment, $mergedPayload) {
                $payment->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'payload' => $mergedPayload,
                ]);

                if ($courseId = ($mergedPayload['course_id'] ?? null)) {
                    $student = $payment->student()->with('user')->first();

                    if (!$student?->user) {
                        Log::warning('LIQPAY: student user not found for course payment', [
                            'payment_id' => $payment->id,
                        ]);
                        return;
                    }

                    $student->user->courses()->syncWithoutDetaching([
                        $courseId => [
                            'status' => 'paid',
                            'paid_amount' => $payment->amount,
                        ],
                    ]);

                    return;
                }

                if ($lessonId = ($mergedPayload['lesson_id'] ?? null)) {
                    $student = $payment->student()->with('user')->first();

                    if (!$student?->user) {
                        Log::warning('LIQPAY: student user not found for lesson payment', [
                            'payment_id' => $payment->id,
                        ]);
                        return;
                    }

                    $student->user->lessons()->syncWithoutDetaching([
                        $lessonId => [
                            'status'      => 'paid',
                            'paid_amount' => $payment->amount,
                        ],
                    ]);

                    return;
                }

                $templateId = $mergedPayload['subscription_template_id'] ?? null;

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

        if (in_array($status, ['failure', 'error'], true)) {
            $payment->update([
                'status' => 'failed',
                'payload' => $mergedPayload,
            ]);

            return response('ok');
        }

        Log::info('LIQPAY: unhandled status', [
            'status' => $status,
            'payment_id' => $payment->id,
        ]);

        return response('ok');
    }
}
