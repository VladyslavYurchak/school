<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\MonoPayPaymentProcessor;
use Illuminate\Console\Command;

class ReconcilePaidPayments extends Command
{
    protected $signature = 'payments:reconcile-paid
        {--apply : Create or link access for paid payments that are missing fulfillment}';

    protected $description = 'Audit paid payments and optionally restore missing subscription or content access';

    public function handle(MonoPayPaymentProcessor $processor): int
    {
        $problems = 0;
        $repaired = 0;

        Payment::query()
            ->where('status', 'paid')
            ->orderBy('id')
            ->each(function (Payment $payment) use ($processor, &$problems, &$repaired): void {
                $status = $processor->paidFulfillmentStatus($payment);

                if ($status === 'fulfilled') {
                    return;
                }

                $problems++;

                if ($this->option('apply') && in_array($status, ['missing', 'missing_link'], true)) {
                    $status = $processor->reconcilePaidPayment($payment);

                    if ($status === 'fulfilled') {
                        $repaired++;
                    }
                }

                $this->line("Payment {$payment->id}: {$status}");
            });

        $this->info("Problems: {$problems}; repaired: {$repaired}.");

        return self::SUCCESS;
    }
}
