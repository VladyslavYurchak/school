<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('telegram:lessons:remind')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);

Schedule::command('telegram:payments:remind')
    ->dailyAt('09:00')
    ->withoutOverlapping(10);

Schedule::command('telegram:payments:confirm')
    ->everyFiveMinutes()
    ->withoutOverlapping(10);
