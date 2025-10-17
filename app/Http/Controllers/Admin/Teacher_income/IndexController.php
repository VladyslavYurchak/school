<?php

namespace App\Http\Controllers\Admin\Teacher_income;

use App\Http\Controllers\Controller;
use App\Models\LessonLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $teacher = Auth::user()->teacher;

        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedYear  = (int) $request->input('year', now()->year);

        $logs = LessonLog::with(['student','group'])
            ->where('teacher_id', $teacher->id)
            ->whereIn('status', ['completed','charged'])
            ->whereYear('date', $selectedYear)
            ->whereMonth('date', $selectedMonth)
            ->get();

        // Хелпер: чиста виплата з логу (без антидублювання)
        $payoutFromLog = function (LessonLog $log) use ($teacher): float {
            if (!is_null($log->teacher_payout_amount)) {
                return (float) $log->teacher_payout_amount; // уже розрахована частка/сума
            }
            // Фолбеки на випадок відсутності snapshot:
            if (!is_null($log->group_id)) {
                return (float) ($log->teacher_rate_amount_at_charge ?? $teacher->group_lesson_price ?? 0);
            }
            return (float) ($log->teacher_rate_amount_at_charge ?? $teacher->lesson_price ?? 0);
        };

        // ### Індивідуальні (рядок = студент)
        $individualRows = [];
        $individualLogs = $logs->filter(fn($l) => is_null($l->group_id)); // або lesson_type === 'individual'

        foreach ($individualLogs as $log) {
            $name   = $log->student->full_name ?? '—';
            $payout = $payoutFromLog($log);

            if (!isset($individualRows[$name])) {
                $individualRows[$name] = [
                    'student'          => (object)['full_name' => $name],
                    'individualCount'  => 0,
                    'groupCount'       => 0,
                    'individualEarned' => 0.0,
                    'groupEarned'      => 0.0,
                    'totalEarned'      => 0.0,
                ];
            }

            $individualRows[$name]['individualCount']++;
            $individualRows[$name]['individualEarned'] += $payout;
            $individualRows[$name]['totalEarned']      += $payout;
        }

        // ### Групові (рядок = група)
        $groupRows = [];
        // Для підрахунку К-сті занять та уникнення подвоєння суми, якщо snapshot не заповнений:
        // Тримаємо "бачені" сесії на рівні групи.
        $seenSessionsPerGroup = [];

        $groupLogs = $logs->filter(fn($l) => !is_null($l->group_id)); // або lesson_type === 'group'

        foreach ($groupLogs as $log) {
            $groupName = $log->group->name ?? ('Група #' . ($log->group_id ?? '—'));

            if (!isset($groupRows[$groupName])) {
                $groupRows[$groupName] = [
                    'student'          => (object)['full_name' => $groupName],
                    'individualCount'  => 0,
                    'groupCount'       => 0,   // рахуємо СЕСІЇ, а не студентів
                    'individualEarned' => 0.0,
                    'groupEarned'      => 0.0,
                    'totalEarned'      => 0.0,
                ];
                $seenSessionsPerGroup[$groupName] = [];
            }

            // Ключ сесії: спочатку lesson_id, інакше група+дата+час
            $time = $log->time ? Carbon::parse($log->time)->format('H:i') : '00:00';
            $sessionKey = $log->lesson_id
                ? 'L:' . $log->lesson_id
                : 'G:' . ($log->group_id ?? '0') . '|' . $log->date . '|' . $time;

            // 1) Лічильник занять (по унікальних сесіях)
            if (!isset($seenSessionsPerGroup[$groupName][$sessionKey])) {
                $seenSessionsPerGroup[$groupName][$sessionKey] = [
                    'counted'      => true,   // для groupCount
                    'amount_added' => false,  // чи додавали суму за сесію у фолбек-режимі
                ];
                $groupRows[$groupName]['groupCount']++; // +1 сесія
            }

            // 2) Сума:
            if (!is_null($log->teacher_payout_amount)) {
                // У тебе вже записані "частки" по кожному студенту (наприклад, по 133 грн) — просто плюсуємо всі.
                $groupRows[$groupName]['groupEarned'] += (float) $log->teacher_payout_amount;
                $groupRows[$groupName]['totalEarned'] += (float) $log->teacher_payout_amount;
            } else {
                // Snapshot суми відсутній: додаємо ставку рівно ОДИН раз на сесію, щоб не множити на студентів.
                if ($seenSessionsPerGroup[$groupName][$sessionKey]['amount_added'] === false) {
                    $amount = (float) ($log->teacher_rate_amount_at_charge ?? $teacher->group_lesson_price ?? 0);
                    $groupRows[$groupName]['groupEarned'] += $amount;
                    $groupRows[$groupName]['totalEarned'] += $amount;
                    $seenSessionsPerGroup[$groupName][$sessionKey]['amount_added'] = true;
                }
            }
        }

        // ### Об’єднання
        // ключ = ім'я студента або назва групи — ок
        $data = $individualRows + $groupRows;

        // Якщо треба стабільний порядок — відсортуємо за ключем
        ksort($data, SORT_NATURAL | SORT_FLAG_CASE);

        return view('admin.teacher_income.index', [
            'selectedMonth' => $selectedMonth,
            'selectedYear'  => $selectedYear,
            'data'          => $data,
        ]);
    }
}
