<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramLessonReminderService;
use Illuminate\Console\Command;

class SendTelegramLessonReminders extends Command
{
    protected $signature = 'telegram:lessons:remind';

    protected $description = 'Send Telegram reminders for lessons starting within one hour';

    public function handle(TelegramLessonReminderService $service): int
    {
        $result = $service->sendDueReminders();

        $this->info(sprintf(
            'Lessons: %d; sent: %d; failed: %d; skipped: %d.',
            $result['lessons'],
            $result['sent'],
            $result['failed'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
