<?php

namespace App\Http\Controllers\Admin\Students\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MoveController extends Controller
{
    public function __invoke(Request $request, Student $student, string $month): JsonResponse
    {
        $data = $request->validate([
            'target_month' => ['required', 'date_format:Y-m'],
        ]);

        if ($data['target_month'] === $month) {
            return response()->json([
                'message' => 'Виберіть інший місяць.',
            ], 422);
        }

        $sourceStart = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $sourceEnd = $sourceStart->copy()->endOfMonth();
        $targetStart = Carbon::createFromFormat('!Y-m', $data['target_month'])->startOfMonth();
        $targetEnd = $targetStart->copy()->endOfMonth();

        $subscription = StudentSubscription::query()
            ->where('student_id', $student->id)
            ->where('type', 'subscription')
            ->whereDate('start_date', $sourceStart->toDateString())
            ->whereDate('end_date', $sourceEnd->toDateString())
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Активний абонемент не знайдено.'], 404);
        }

        if ($subscription->hasRecordedLessons()) {
            return response()->json([
                'message' => 'Абонемент не можна перенести: за ним уже є проведене або зараховане заняття.',
            ], 422);
        }

        $targetExists = StudentSubscription::query()
            ->where('student_id', $student->id)
            ->where('type', 'subscription')
            ->whereKeyNot($subscription->id)
            ->whereDate('start_date', $targetStart->toDateString())
            ->whereDate('end_date', $targetEnd->toDateString())
            ->whereIn('status', ['pending', 'active'])
            ->exists();

        if ($targetExists) {
            return response()->json([
                'message' => 'На вибраний місяць уже є активний абонемент.',
            ], 422);
        }

        DB::transaction(function () use ($subscription, $student, $month, $targetStart, $targetEnd) {
            $subscription->update([
                'start_date' => $targetStart->toDateString(),
                'end_date' => $targetEnd->toDateString(),
            ]);

            $payment = $subscription->payment;

            if (!$payment) {
                return;
            }

            $payload = is_array($payment->payload) ? $payment->payload : [];

            $payment->update([
                'description' => $this->paymentDescription($targetStart, $student),
                'payload' => array_merge($payload, [
                    'subscription_month' => $targetStart->format('Y-m'),
                    'moved_by_admin_from_month' => $month,
                    'moved_by_admin_at' => now()->toISOString(),
                ]),
            ]);
        });

        return response()->json([
            'message' => 'Абонемент успішно перенесено.',
        ]);
    }

    private function paymentDescription(Carbon $month, Student $student): string
    {
        $months = [
            1 => 'січень',
            2 => 'лютий',
            3 => 'березень',
            4 => 'квітень',
            5 => 'травень',
            6 => 'червень',
            7 => 'липень',
            8 => 'серпень',
            9 => 'вересень',
            10 => 'жовтень',
            11 => 'листопад',
            12 => 'грудень',
        ];

        return sprintf(
            'Оплата за навчання за період %s %s - %s',
            $months[(int) $month->month],
            $month->format('Y'),
            $student->full_name
        );
    }
}
