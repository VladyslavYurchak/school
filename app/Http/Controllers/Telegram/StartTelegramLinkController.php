<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramLinkService;
use Illuminate\Http\Request;

class StartTelegramLinkController extends Controller
{
    public function __invoke(Request $request, TelegramLinkService $linkService)
    {
        abort_unless(
            $request->user()?->isStudent() || $request->user()?->isTeacher(),
            403,
        );

        $username = ltrim(trim((string) config('services.telegram.bot_username')), '@');

        if (! preg_match('/^[A-Za-z0-9_]{5,32}$/', $username)) {
            return back()->with('telegram_error', 'Telegram-бот ще не налаштований.');
        }

        $token = $linkService->issue($request->user());

        return redirect()->away("https://t.me/{$username}?start={$token}");
    }
}
