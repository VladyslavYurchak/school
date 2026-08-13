<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DisconnectTelegramController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless(
            $request->user()?->isStudent() || $request->user()?->hasActiveTeacherProfile(),
            403,
        );

        $request->user()->telegramAccount()->delete();
        $request->user()->telegramLinkTokens()->delete();

        return back()->with('telegram_success', 'Telegram успішно від’єднано.');
    }
}
