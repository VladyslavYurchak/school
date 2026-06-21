<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Services\MonoPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonoPayWebhookController extends Controller
{
    public function __invoke(Request $request, MonoPayService $monoPay): Response
    {
        $rawBody = $request->getContent();

        Log::info('MONOPAY WEBHOOK RAW', [
            'body' => $rawBody,
            'headers' => $request->headers->all(),
        ]);

        $monoPayload = json_decode($rawBody, true);

        if (!is_array($monoPayload)) {
            Log::warning('MONOPAY: invalid payload');

            return response('invalid payload', 400);
        }

        $invoiceId = $monoPayload['invoiceId'] ?? null;
        $reference = $monoPayload['reference'] ?? null;
        $status = $monoPayload['status'] ?? null;

        if (!$invoiceId && !$reference) {
            Log::warning('MONOPAY: missing invoiceId and reference');

            return response('bad request', 400);
        }

        $payment = Payment::query()
            ->where('provider', 'monopay')
            ->where(function ($query) use ($invoiceId, $reference) {
                if ($invoiceId) {
                    $query->orWhere('provider_payment_id', $invoiceId);
                }

                if ($reference) {
                    $query->orWhere('provider_order_id', $reference);
                }
            })
            ->first();

        if (!$payment) {
            Log::warning('MONOPAY: payment not found', [
                'invoiceId' => $invoiceId,
                'reference' => $reference,
            ]);

            return response('payment not found', 404);
        }

        if (!$this->isValidSignature($request, $rawBody, $monoPay)) {
            Log::warning('MONOPAY: invalid signature, trying invoice status fallback', [
                'payment_id' => $payment->id,
                'invoiceId' => $invoiceId,
                'reference' => $reference,
            ]);

            $verifiedPayload = $this->verifiedPayloadFromInvoiceStatus($monoPay, $payment, $invoiceId, $reference);

            if (!$verifiedPayload) {
                Log::warning('MONOPAY: invoice status fallback failed', [
                    'payment_id' => $payment->id,
                    'invoiceId' => $invoiceId,
                    'reference' => $reference,
                ]);

                return response('invalid signature', 400);
            }

            $monoPayload = array_merge($monoPayload, $verifiedPayload, [
                'verified_by_invoice_status' => true,
            ]);

            $invoiceId = $monoPayload['invoiceId'] ?? $invoiceId;
            $reference = $monoPayload['reference'] ?? $reference;
            $status = $monoPayload['status'] ?? $status;
        }

        $paymentPayload = is_array($payment->payload) ? $payment->payload : [];

        $mergedPayload = array_merge($paymentPayload, [
            'mono_webhook' => $monoPayload,
        ]);

        $payment->update([
            'provider_payment_id' => $invoiceId ?: $payment->provider_payment_id,
            'payload' => $mergedPayload,
        ]);

        if ($status === 'success') {
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
                        Log::warning('MONOPAY: student user not found for course payment', [
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
                        Log::warning('MONOPAY: student user not found for lesson payment', [
                            'payment_id' => $payment->id,
                        ]);
                        return;
                    }

                    $student->user->lessons()->syncWithoutDetaching([
                        $lessonId => [
                            'status' => 'paid',
                            'paid_amount' => $payment->amount,
                        ],
                    ]);

                    return;
                }

                $templateId = $mergedPayload['subscription_template_id'] ?? null;

                if (!$templateId) {
                    Log::warning('MONOPAY: subscription_template_id not found in payment payload', [
                        'payment_id' => $payment->id,
                    ]);
                    return;
                }

                $template = SubscriptionTemplate::find($templateId);

                if (!$template) {
                    Log::warning('MONOPAY: template not found', [
                        'template_id' => $templateId,
                        'payment_id' => $payment->id,
                    ]);
                    return;
                }

                $subscriptionMonth = $mergedPayload['subscription_month'] ?? null;

                $startDate = $subscriptionMonth
                    ? Carbon::createFromFormat('Y-m', $subscriptionMonth)->startOfMonth()
                    : Carbon::now()->startOfMonth();

                $endDate = (clone $startDate)->endOfMonth();

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

        if (in_array($status, ['failure', 'expired'], true)) {
            $payment->update([
                'status' => 'failed',
                'payload' => $mergedPayload,
            ]);

            return response('ok');
        }

        if ($status === 'reversed') {
            DB::transaction(function () use ($payment, $mergedPayload) {
                $payment->update([
                    'status' => 'refunded',
                    'payload' => $mergedPayload,
                ]);

                $student = $payment->student()->with('user')->first();

                if ($student?->user && ($courseId = ($mergedPayload['course_id'] ?? null))) {
                    $student->user->courses()->updateExistingPivot($courseId, [
                        'status' => 'refunded',
                    ]);
                }

                if ($student?->user && ($lessonId = ($mergedPayload['lesson_id'] ?? null))) {
                    $student->user->lessons()->updateExistingPivot($lessonId, [
                        'status' => 'refunded',
                    ]);
                }

                $payment->subscriptions()
                    ->whereIn('status', ['pending', 'active'])
                    ->update(['status' => 'cancelled']);
            });

            return response('ok');
        }

        Log::info('MONOPAY: unhandled status', [
            'status' => $status,
            'payment_id' => $payment->id,
        ]);

        return response('ok');
    }

    private function isValidSignature(Request $request, string $rawBody, MonoPayService $monoPay): bool
    {
        $signature = $request->header('X-Sign') ?: $request->header('x-sign');

        if (!$signature) {
            return false;
        }

        try {
            $publicKeyPem = $this->normalizePublicKey($monoPay->getPublicKey());

            $publicKeyResource = openssl_pkey_get_public($publicKeyPem);

            if (!$publicKeyResource) {
                Log::warning('MONOPAY: cannot parse public key');

                return false;
            }

            return openssl_verify(
                    $rawBody,
                    base64_decode($signature),
                    $publicKeyResource,
                    OPENSSL_ALGO_SHA256
                ) === 1;
        } catch (\Throwable $e) {
            Log::error('MONOPAY SIGNATURE VERIFY ERROR', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizePublicKey(string $publicKey): string
    {
        $publicKey = trim($publicKey);

        if (str_contains($publicKey, '-----BEGIN PUBLIC KEY-----')) {
            return $publicKey;
        }

        $publicKey = preg_replace('/\s+/', '', $publicKey);

        return "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split($publicKey, 64, "\n")
            . "-----END PUBLIC KEY-----\n";
    }

    private function verifiedPayloadFromInvoiceStatus(
        MonoPayService $monoPay,
        Payment $payment,
        ?string $invoiceId,
        ?string $reference
    ): ?array {
        if (!$invoiceId) {
            return null;
        }

        try {
            $invoice = $monoPay->getInvoiceStatus($invoiceId);
        } catch (\Throwable $e) {
            Log::error('MONOPAY INVOICE STATUS FALLBACK ERROR', [
                'payment_id' => $payment->id,
                'invoice_id' => $invoiceId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        $invoiceReference = $invoice['reference'] ?? null;
        $invoiceAmount = isset($invoice['amount']) ? (int) $invoice['amount'] : null;
        $paymentAmount = (int) round(((float) $payment->amount) * 100);

        if (($invoice['invoiceId'] ?? null) !== $invoiceId) {
            return null;
        }

        if ($reference && $invoiceReference && $invoiceReference !== $reference) {
            return null;
        }

        if ($invoiceReference && $invoiceReference !== $payment->provider_order_id) {
            return null;
        }

        if ($invoiceAmount !== null && $invoiceAmount !== $paymentAmount) {
            return null;
        }

        return $invoice;
    }
}
