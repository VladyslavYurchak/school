<?php

namespace App\Http\Controllers\Admin\Students\Subscription\Single;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentSubscription;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IndexController extends Controller
{
    public function __invoke(Request $request, Student $student)
    {
        $month = $request->query('month'); // очікуємо Y-m
        $query = StudentSubscription::where('student_id', $student->id)
            ->where('type', 'single')
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if ($month) {
            try {
                $start = Carbon::createFromFormat('Y-m', $month, 'Europe/Kyiv')->startOfMonth()->toDateString();
                $end   = Carbon::createFromFormat('Y-m', $month, 'Europe/Kyiv')->endOfMonth()->toDateString();
                $query->whereBetween('start_date', [$start, $end]);
            } catch (\Exception $e) {
                // ігноруємо кривий формат — просто покажемо все
            }
        }

        $payments = $query->get();

        // якщо AJAX — повертаємо часткове view для підстановки у модалку
        if ($request->ajax()) {
            return response()->view('admin.students.partials.single_payments_table', [
                'student'  => $student,
                'payments' => $payments,
            ]);
        }

        // опційно — як JSON, якщо треба
        return response()->json([
            'student_id' => $student->id,
            'payments'   => $payments,
        ]);
    }
}
