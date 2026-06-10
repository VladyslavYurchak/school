<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonoPayService
{
    private string $baseUrl = 'https://api.monobank.ua';

    public function createInvoice(Payment $payment): array
    {
        $token = config('services.monopay.token');

        if (!$token) {
            throw new \RuntimeException('MONOPAY_TOKEN is not configured.');
        }

        $amountInKopecks = (int) round(((float) $payment->amount) * 100);

        $response = Http::withHeaders([
            'X-Token' => $token,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/api/merchant/invoice/create', [
            'amount' => $amountInKopecks,
            'ccy' => 980,
            'merchantPaymInfo' => [
                'reference' => $payment->provider_order_id,
                'destination' => $payment->description,
                'comment' => $payment->description,
            ],
            'redirectUrl' => route('student.payments.result'),
            'webHookUrl' => config('services.monopay.webhook_url') ?: route('monopay.webhook'),
            'validity' => 3600,
            'paymentType' => 'debit',
        ]);

        if (!$response->successful()) {
            Log::error('MONOPAY CREATE INVOICE ERROR', [
                'payment_id' => $payment->id,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Cannot create MonoPay invoice.');
        }

        return $response->json();
    }

    public function getPublicKey(): string
    {
        $token = config('services.monopay.token');

        $response = Http::withHeaders([
            'X-Token' => $token,
        ])->get($this->baseUrl . '/api/merchant/pubkey');

        if (!$response->successful()) {
            throw new \RuntimeException('Cannot get MonoPay public key.');
        }

        return $response->json('key');
    }
}
