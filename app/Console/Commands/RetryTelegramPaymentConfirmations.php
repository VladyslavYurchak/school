<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramPaymentConfirmationService;
use Illuminate\Console\Command;

class RetryTelegramPaymentConfirmations extends Command
{
    protected $signature = 'telegram:payments:confirm';

    protected $description = 'Retry pending Telegram payment confirmations';

    public function handle(TelegramPaymentConfirmationService $service): int
    {
        $result = $service->retryPending();

        $this->info(sprintf(
            'Confirmations: %d; sent: %d; failed: %d; skipped: %d.',
            $result['confirmations'],
            $result['sent'],
            $result['failed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
