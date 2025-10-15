<?php

namespace App\Http\Controllers\Admin\Students\Subscription;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    public function __invoke ($studentId, $month)
    {
        $startDate = Carbon::parse($month . '-01')->startOfMonth();
        $endDate = Carbon::parse($month . '-01')->endOfMonth();

        $subscription = StudentSubscription::where('student_id', $studentId)
            ->whereDate('start_date', $startDate)
            ->whereDate('end_date', $endDate)
            ->first();

        if (!$subscription) {
            return response()->json(['message' => 'Оплата не знайдена'], 404);
        }

        $subscription->delete();

        return response()->json(['message' => 'Оплата скасована']);
    }
}
