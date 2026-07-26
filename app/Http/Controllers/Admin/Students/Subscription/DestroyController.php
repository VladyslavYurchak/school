<?php

namespace App\Http\Controllers\Admin\Students\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DestroyController extends Controller
{
    public function __invoke(Student $student, string $month)
    {
        $startDate = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $subscription = StudentSubscription::query()
            ->where('student_id', $student->id)
            ->where('type', 'subscription')
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->whereIn('status', ['pending', 'active'])
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Активний абонемент не знайдено.'], 404);
        }

        if ($subscription->hasRecordedLessons()) {
            return response()->json([
                'message' => 'Повернення неможливе: за абонементом уже є проведене або зараховане заняття.',
            ], 422);
        }

        DB::transaction(function () use ($subscription) {
            $subscription->update(['status' => 'cancelled']);

            $payment = $subscription->payment;

            if (!$payment || $payment->status !== 'paid') {
                return;
            }

            $payload = is_array($payment->payload) ? $payment->payload : [];

            $payment->update([
                'status' => 'refunded',
                'payload' => array_merge($payload, [
                    'refunded_manually_by_admin' => true,
                    'refunded_manually_by_admin_at' => now()->toISOString(),
                ]),
            ]);
        });

        return response()->json([
            'message' => 'Абонемент скасовано, ручне повернення зафіксовано.',
        ]);
    }
}
