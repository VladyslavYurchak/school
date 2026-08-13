<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\MonoPayPaymentProcessor;
use App\Services\MonoPayService;
use App\Services\Telegram\TelegramPaymentConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MonoPayWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MonoPayService $monoPay,
        MonoPayPaymentProcessor $processor,
        TelegramPaymentConfirmationService $paymentConfirmation,
    ): Response {
        $rawBody = $request->getContent();

        $monoPayload = json_decode($rawBody, true);

        if (! is_array($monoPayload)) {
            Log::warning('MONOPAY: invalid payload');

            return response('invalid payload', 400);
        }

        $invoiceId = $monoPayload['invoiceId'] ?? null;
        $reference = $monoPayload['reference'] ?? null;
        $status = $monoPayload['status'] ?? null;

        Log::info('MONOPAY: webhook received', [
            'invoiceId' => $invoiceId,
            'reference' => $reference,
            'status' => $status,
        ]);

        if (! $invoiceId && ! $reference) {
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

        if (! $payment) {
            Log::warning('MONOPAY: payment not found', [
                'invoiceId' => $invoiceId,
                'reference' => $reference,
            ]);

            return response('payment not found', 404);
        }

        if (! $this->isValidSignature($request, $rawBody, $monoPay)) {
            Log::warning('MONOPAY: invalid signature, trying invoice status fallback', [
                'payment_id' => $payment->id,
                'invoiceId' => $invoiceId,
                'reference' => $reference,
            ]);

            $verifiedPayload = $this->verifiedPayloadFromInvoiceStatus(
                $monoPay,
                $processor,
                $payment,
                $invoiceId
            );

            if (! $verifiedPayload) {
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

        if (! $processor->invoiceMatchesPayment($payment, $monoPayload)) {
            Log::warning('MONOPAY: invoice does not match payment', [
                'payment_id' => $payment->id,
                'invoiceId' => $invoiceId,
                'reference' => $reference,
            ]);

            return response('payment mismatch', 400);
        }

        try {
            $processor->process($payment, $monoPayload);
        } catch (\Throwable $e) {
            Log::error('MONOPAY: payment fulfillment failed', [
                'payment_id' => $payment->id,
                'status' => $status,
                'message' => $e->getMessage(),
            ]);

            return response('payment fulfillment failed', 409);
        }

        if ($status === 'success') {
            try {
                $paymentConfirmation->sendForPayment($payment->fresh());
            } catch (\Throwable $e) {
                Log::warning('MONOPAY: Telegram payment confirmation failed', [
                    'payment_id' => $payment->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        if (! in_array($status, ['success', 'failure', 'expired', 'reversed'], true)) {
            Log::info('MONOPAY: unhandled status', [
                'status' => $status,
                'payment_id' => $payment->id,
            ]);
        }

        return response('ok');
    }

    private function isValidSignature(Request $request, string $rawBody, MonoPayService $monoPay): bool
    {
        $signature = $request->header('X-Sign') ?: $request->header('x-sign');

        if (! $signature) {
            return false;
        }

        try {
            $publicKeyPem = $this->normalizePublicKey($monoPay->getPublicKey());

            $publicKeyResource = openssl_pkey_get_public($publicKeyPem);

            if (! $publicKeyResource) {
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
            .chunk_split($publicKey, 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    private function verifiedPayloadFromInvoiceStatus(
        MonoPayService $monoPay,
        MonoPayPaymentProcessor $processor,
        Payment $payment,
        ?string $invoiceId
    ): ?array {
        if (! $invoiceId) {
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

        return $processor->invoiceMatchesPayment($payment, $invoice) ? $invoice : null;
    }
}
