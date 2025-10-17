<?php

namespace App\Models;

use App\Enums\LessonLogStatus;
use App\Enums\LessonType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'email',
        'lesson_price',
        'group_lesson_price',
        'trial_lesson_price',
        'pair_lesson_price',
        'note',
        'is_active'
    ];

    /** Один викладач має багато студентів. */
    public function students()
    {
        return $this->hasMany(User::class, 'teacher_id');
    }

    /** Один викладач має багато записів занять. */
    public function lessonLogs()
    {
        return $this->hasMany(LessonLog::class);
    }

    /** Отримати повне імʼя викладача. */
    public function getFullNameAttribute()
    {
        return "{$this->last_name} {$this->first_name}";
    }

    /**
     * Повертає кількість індивідуальних і групових занять за місяць.
     * Індивідуальні: логи типів ['individual','trial'] (1 лог = 1 урок)
     * Групові: рахуємо КІЛЬКІСТЬ УРОКІВ, а не логів -> distinct за lesson_id (fallback на group/date/time, якщо lesson_id відсутній)
     */
    public function getMonthLessonCounts(int $year, int $month): array
    {
        $base = $this->lessonLogs()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['completed', 'charged']);

        $individualCount = (clone $base)
            ->whereIn('lesson_type', ['individual', 'trial'])
            ->count();

        // Групові уроки: distinct по lesson_id (може бути кілька логів на один урок)
        $groupDistinctByLessonId = (clone $base)
            ->whereIn('lesson_type', ['group', 'pair'])
            ->whereNotNull('lesson_id')
            ->distinct()
            ->count('lesson_id');

        // Fallback, якщо старі логи без lesson_id
        if ($groupDistinctByLessonId === 0) {
            $groupDistinctByLessonId = (clone $base)
                ->whereIn('lesson_type', ['group', 'pair'])
                ->get()
                ->groupBy(function ($log) {
                    $timeStr = \Carbon\Carbon::parse($log->time)->format('H:i');
                    return $log->group_id . '|' . $log->date . '|' . $timeStr;
                })->count();
        }

        return [
            'individual' => $individualCount,
            'group'      => $groupDistinctByLessonId,
        ];
    }

    /**
     * МІСЯЧНА ЗАРПЛАТА викладача (сума snapshot-полів, а не поточні ціни!)
     */
    public function getMonthSalary(int $year, int $month): float
    {
        return (float) $this->lessonLogs()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['completed', 'charged'])
            ->sum('teacher_payout_amount');
    }

    public function plannedLessons()
    {
        return $this->hasMany(PlannedLesson::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    protected function monthLogsBase(int $year, int $month)
    {
        return $this->lessonLogs()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', [LessonLogStatus::Completed->value, LessonLogStatus::Charged->value]);
    }

    /** К-сть занять за МІСЯЦЬ окремо: individual, trial, group, pair. */
    public function getMonthLessonCountsDetailed(int $year, int $month): array
    {
        $base = $this->monthLogsBase($year, $month);

        // індивідуальні — БЕЗ пробних
        $individual = (clone $base)
            ->where('lesson_type', LessonType::Individual->value)
            ->count();

        // пробні — окремо
        $trial = (clone $base)
            ->where('lesson_type', LessonType::Trial->value)
            ->count();

        // групові — distinct за lesson_id (fallback)
        $group = $this->distinctCountByLessonOrFallback(
            (clone $base)->where('lesson_type', LessonType::Group->value)
        );

        // парні — distinct за lesson_id (fallback)
        $pair = $this->distinctCountByLessonOrFallback(
            (clone $base)->where('lesson_type', LessonType::Pair->value)
        );

        return compact('individual', 'trial', 'group', 'pair');
    }

    /** Допоміжний: distinct за lesson_id з fallback на group|date|time. */
    protected function distinctCountByLessonOrFallback($query): int
    {
        $byLessonId = (clone $query)
            ->whereNotNull('lesson_id')
            ->distinct()
            ->count('lesson_id');
        if ($byLessonId > 0) {
            return $byLessonId;
        }

        $logs = $query->get();
        return $logs->groupBy(function ($log) {
            $timeStr = Carbon::parse($log->time)->format('H:i');
            return ($log->group_id ?? 'no-group') . '|' . $log->date . '|' . $timeStr;
        })->count();
    }

    /** Суми snapshot-виплат (зарплати) за МІСЯЦЬ окремо: individual, trial, group, pair. */
    public function getMonthCostsByType(int $year, int $month): array
    {
        $base = $this->monthLogsBase($year, $month);

        $individual = (float) (clone $base)
            ->where('lesson_type', LessonType::Individual->value)
            ->sum('teacher_payout_amount');

        $trial = (float) (clone $base)
            ->where('lesson_type', LessonType::Trial->value)
            ->sum('teacher_payout_amount');

        $group = (float) (clone $base)
            ->where('lesson_type', LessonType::Group->value)
            ->sum('teacher_payout_amount');

        $pair = (float) (clone $base)
            ->where('lesson_type', LessonType::Pair->value)
            ->sum('teacher_payout_amount');

        return compact('individual', 'trial', 'group', 'pair');
    }

    /** Ідентифікатори студентів, що МАЛИ логи в цьому місяці з будь-яким із типів. */
    public function getStudentIdsByLessonTypesAndMonth(array $types, int $year, int $month)
    {
        return $this->monthLogsBase($year, $month)
            ->whereIn('lesson_type', $types)
            ->pluck('student_id')
            ->unique();
    }

    /**
     * Сума оплат з підписок по студентам, які мали логи потрібних lesson_type в цьому місяці.
     * (Твій поточний дизайн: прив'язка доходу до активності студентів за типом.)
     */
    public function getSubscriptionSumByLessonTypes(array $types, int $year, int $month): float
    {
        $studentIds = $this->getStudentIdsByLessonTypesAndMonth($types, $year, $month);

        return (float) \App\Models\StudentSubscription::whereIn('student_id', $studentIds)
            ->where(function ($q) use ($year, $month) {
                $q->whereYear('start_date', $year)->whereMonth('start_date', $month)
                    ->orWhereYear('end_date', $year)->whereMonth('end_date', $month);
            })
            ->whereIn('type', ['subscription', 'single'])
            ->sum('price');
    }

    /**
     * Детальний місячний “прибуток” по типах:
     * - revenue_*: надходження з підписок (за методом вище)
     * - costs_*: snapshot-виплати викладачу
     * - income_*: revenue_* - costs_*
     * - trial_revenue зазвичай 0 (пробні не продаються як абонемент), але лишаємо поле для гнучкості.
     */
    public function getMonthlyIncomeDetailed(int $year, int $month): array
    {
        $counts = $this->getMonthLessonCountsDetailed($year, $month);
        $costs  = $this->getMonthCostsByType($year, $month);

        // надходження
        $revenue_individual = $this->getSubscriptionSumByLessonTypes([LessonType::Individual->value], $year, $month);
        $revenue_group      = $this->getSubscriptionSumByLessonTypes([LessonType::Group->value], $year, $month);
        $revenue_pair       = $this->getSubscriptionSumByLessonTypes([LessonType::Pair->value], $year, $month);
        $revenue_trial      = 0.0; // якщо будуть платні пробні — тут можна додати джерело

        // прибуток по типах
        $income_individual = $revenue_individual - $costs['individual'];
        $income_group      = $revenue_group      - $costs['group'];
        $income_pair       = $revenue_pair       - $costs['pair'];
        $income_trial      = $revenue_trial      - $costs['trial']; // скоріш за все від'ємне або 0

        // підсумок
        $total_income = $income_individual + $income_group + $income_pair + $income_trial;

        return [
            // к-сті
            'counts'  => $counts, // ['individual','trial','group','pair']

            // собівартість (зарплата) по типах
            'costs'   => $costs, // ['individual','trial','group','pair']

            // надходження по типах
            'revenue' => [
                'individual' => $revenue_individual,
                'trial'      => $revenue_trial,
                'group'      => $revenue_group,
                'pair'       => $revenue_pair,
            ],

            // прибуток по типах
            'income'  => [
                'individual' => $income_individual,
                'trial'      => $income_trial,
                'group'      => $income_group,
                'pair'       => $income_pair,
            ],

            // загальний прибуток
            'total_income' => $total_income,
        ];
    }
}
