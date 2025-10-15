<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',      // додай це поле сюди
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

    /**
     * Один викладач має багато студентів.
     */
    public function students()
    {
        return $this->hasMany(User::class, 'teacher_id');
    }

    /**
     * Один викладач має багато записів занять.
     */
    public function lessonLogs()
    {
        return $this->hasMany(LessonLog::class);
    }

    /**
     * Отримати повне імʼя викладача.
     */
    public function getFullNameAttribute()
    {
        return "{$this->last_name} {$this->first_name}";
    }

    public function getMonthLessonCounts($year, $month)
    {
        $logs = $this->lessonLogs
            ->filter(function ($log) use ($year, $month) {
                $date = \Carbon\Carbon::parse($log->date);
                return $date->year == $year && $date->month == $month && in_array($log->status, ['completed', 'charged']);
            });

        // Порахувати індивідуальні
        $individualCount = $logs->whereNull('group_id')->count();

        // Порахувати унікальні групові уроки
        $groupUniqueLessons = $logs->whereNotNull('group_id')
            ->groupBy(function ($log) {
                return $log->group_id . '|' . $log->date . '|' . \Carbon\Carbon::parse($log->time)->format('H:i');
            });

        $groupCount = $groupUniqueLessons->count();

        return [
            'individual' => $individualCount,
            'group' => $groupCount,
        ];
    }


    public function getMonthSalary($year, $month)
    {
        $counts = $this->getMonthLessonCounts($year, $month);
        return ($counts['individual'] * ($this->lesson_price ?? 0)) + ($counts['group'] * ($this->group_lesson_price ?? 0));
    }


    public function plannedLessons()
    {
        return $this->hasMany(PlannedLesson::class);
    }

    // app/Models/Teacher.php
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }




    public function getStudentIdsByLessonTypeAndMonth(string $type, int $year, int $month)
    {
        return $this->lessonLogs()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['completed', 'charged'])
            ->when($type === 'individual', fn($q) => $q->whereNull('group_id'))
            ->when($type === 'group', fn($q) => $q->whereNotNull('group_id'))
            ->pluck('student_id')
            ->unique();
    }

    // Отримати кількість занять (індивідуальних або групових) за місяць (для групових враховує унікальні заняття по даті+часу)
    public function getLessonCountByTypeAndMonth(string $type, int $year, int $month)
    {
        $logs = $this->lessonLogs()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->whereIn('status', ['completed', 'charged']);

        if ($type === 'individual') {
            return $logs->whereNull('group_id')->count();
        }

        if ($type === 'group') {
            // Порахуємо кількість унікальних занять по group_id + date + time
            $groupLogs = $logs->whereNotNull('group_id')->get();

            return $groupLogs->groupBy(function ($log) {
                // Робимо time у форматі 'H:i'
                $timeStr = \Carbon\Carbon::parse($log->time)->format('H:i');
                return $log->group_id . '|' . $log->date . '|' . $timeStr;
            })->count();
        }

        return 0;
    }



    // Отримати суму оплат студентів за типом (індивідуальні або групові) за місяць
    public function getSubscriptionSumByTypeAndMonth(string $type, int $year, int $month)
    {
        $studentIds = $this->getStudentIdsByLessonTypeAndMonth(
            $type,
            $year,
            $month
        );

        if ($type === 'individual') {
            // Для індивідуальних беремо підписки типу subscription або single,
            // які співпадають з місяцем (start_date або end_date у місяці)
            return \App\Models\StudentSubscription::whereIn('student_id', $studentIds)
                ->where(function ($query) use ($year, $month) {
                    $query->whereYear('start_date', $year)->whereMonth('start_date', $month)
                        ->orWhereYear('end_date', $year)->whereMonth('end_date', $month);
                })
                ->whereIn('type', ['subscription', 'single'])
                ->sum('price');
        }

        if ($type === 'group') {
            // Аналогічно для групових, але студентів, які у групових заняттях
            return \App\Models\StudentSubscription::whereIn('student_id', $studentIds)
                ->where(function ($query) use ($year, $month) {
                    $query->whereYear('start_date', $year)->whereMonth('start_date', $month)
                        ->orWhereYear('end_date', $year)->whereMonth('end_date', $month);
                })
                ->whereIn('type', ['subscription', 'single'])
                ->sum('price');
        }

        return 0;
    }

    // Головний метод: отримаємо дохід викладача за місяць (індивідуальний, груповий, загальний)
    public function getMonthlyIncome(int $year, int $month)
    {
        $individualLessonsCount = $this->getLessonCountByTypeAndMonth('individual', $year, $month);
        $groupLessonsCount = $this->getLessonCountByTypeAndMonth('group', $year, $month);

        $individualSubscriptionsSum = $this->getSubscriptionSumByTypeAndMonth('individual', $year, $month);
        $groupSubscriptionsSum = $this->getSubscriptionSumByTypeAndMonth('group', $year, $month);

        $individualExpenses = $individualLessonsCount * ($this->lesson_price ?? 0);
        $groupExpenses = $groupLessonsCount * ($this->group_lesson_price ?? 0);

        $incomeIndividual = $individualSubscriptionsSum - $individualExpenses;
        $incomeGroup = $groupSubscriptionsSum - $groupExpenses;

        return [
            'individual_lessons_count' => $individualLessonsCount,
            'group_lessons_count' => $groupLessonsCount,
            'individual_subscriptions_sum' => $individualSubscriptionsSum,
            'group_subscriptions_sum' => $groupSubscriptionsSum,
            'individual_income' => $incomeIndividual,
            'group_income' => $incomeGroup,
            'total_income' => $incomeIndividual + $incomeGroup,
        ];
    }


}
