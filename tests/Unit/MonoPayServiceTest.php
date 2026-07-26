<?php

namespace Tests\Unit;

use App\Models\Payment;
use App\Models\Student;
use App\Services\MonoPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MonoPayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_return_url_contains_local_payment_id(): void
    {
        config()->set('services.monopay.token', 'test-token');

        $payment = Payment::create([
            'student_id' => Student::factory()->create()->id,
            'amount' => 500,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'order-123',
            'description' => 'Course payment',
        ]);

        Http::fake([
            'https://api.monobank.ua/api/merchant/invoice/create' => Http::response([
                'invoiceId' => 'invoice-123',
                'pageUrl' => 'https://pay.example/invoice-123',
            ]),
        ]);

        app(MonoPayService::class)->createInvoice($payment);

        Http::assertSent(function ($request) use ($payment) {
            return $request->url() === 'https://api.monobank.ua/api/merchant/invoice/create'
                && $request['redirectUrl'] === route('student.payments.result', [
                    'payment' => $payment->id,
                ])
                && $request['webHookUrl'] === route('monopay.webhook')
                && $request['amount'] === 50000
                && $request['merchantPaymInfo']['reference'] === 'order-123';
        });
    }
}
