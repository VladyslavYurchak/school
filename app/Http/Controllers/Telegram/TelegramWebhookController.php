<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $bot): JsonResponse
    {
        $expectedSecret = trim((string) config('services.telegram.webhook_secret'));
        $receivedSecret = (string) $request->header('X-Telegram-Bot-Api-Secret-Token');

        abort_unless(
            $expectedSecret !== ''
            && $receivedSecret !== ''
            && hash_equals($expectedSecret, $receivedSecret),
            403
        );

        $bot->handle($request->all());

        return response()->json(['ok' => true]);
    }
}
