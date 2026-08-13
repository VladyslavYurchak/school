<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramPaymentReminderService;
use Illuminate\Console\Command;

class SendTelegramPaymentReminders extends Command
{
    protected $signature = 'telegram:payments:remind';

    protected $description = 'Send Telegram reminders for unpaid student subscriptions';

    public function handle(TelegramPaymentReminderService $service): int
    {
        $result = $service->sendDueReminders();

        $this->info(sprintf(
            'Reminders: %d; sent: %d; failed: %d; skipped: %d.',
            $result['reminders'],
            $result['sent'],
            $result['failed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
