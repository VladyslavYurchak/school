<?php

declare(strict_types=1);

namespace App\Services\Data;

use App\Enums\LessonLogStatus;
use App\Models\Teacher;

class TeacherMonthlyReportService
{
    public function build(int $year, int $month): array
    {
        $teachers = Teacher::query()
            ->where(function ($query) use ($year, $month) {
                $query->where('is_active', true)
                    ->orWhereHas('lessonLogs', function ($logs) use ($year, $month) {
                        $logs->whereYear('date', $year)
                            ->whereMonth('date', $month)
                            ->whereIn('status', [
                                LessonLogStatus::Completed->value,
                                LessonLogStatus::Charged->value,
                            ]);
                    });
            })
            ->orderBy('id')
            ->get();

        $rows = [];
        $totals = [
            // totals для кількостей і ЗП
            'cnt_individual' => 0, 'cnt_trial' => 0, 'cnt_group' => 0, 'cnt_pair' => 0,
            'salary_total'   => 0.0,
            // totals для rev/cost/inc
            'rev_ind' => 0.0, 'cost_ind' => 0.0, 'inc_ind' => 0.0,
            'rev_trial' => 0.0, 'cost_trial' => 0.0, 'inc_trial' => 0.0,
            'rev_grp' => 0.0, 'cost_grp' => 0.0, 'inc_grp' => 0.0,
            'rev_pair' => 0.0, 'cost_pair' => 0.0, 'inc_pair' => 0.0,
            'profit_total' => 0.0,
        ];

        foreach ($teachers as $teacher) {
            // КІЛЬКОСТІ + ЗП
            $counts = $teacher->getMonthLessonCountsDetailed($year, $month);
            $costs  = $teacher->getMonthCostsByType($year, $month);

            $cntInd  = $counts['individual'];
            $cntTr   = $counts['trial'];
            $cntGrp  = $counts['group'];
            $cntPair = $counts['pair'];

            $salary = $teacher->getMonthSalary($year, $month);

            // 💰 ДОХОДИ ЛИШЕ З ПІДПИСОК
            $revInd  = $teacher->getSubscriptionSumByLessonTypes(['individual'], $year, $month);
            $revGrp  = $teacher->getSubscriptionSumByLessonTypes(['group'], $year, $month);
            $revPair = $teacher->getSubscriptionSumByLessonTypes(['pair'], $year, $month);
            $revTr   = 0.0; // якщо будуть платні пробні — тут підставиш своє джерело

            // СОБІВАРТІСТЬ (виплати викладачу з логів)
            $costInd  = $costs['individual'];
            $costTr   = $costs['trial'];
            $costGrp  = $costs['group'];
            $costPair = $costs['pair'];

            // ПРИБУТОК
            $incInd  = $revInd  - $costInd;
            $incTr   = $revTr   - $costTr;
            $incGrp  = $revGrp  - $costGrp;
            $incPair = $revPair - $costPair;

            $profitTotal = $incInd + $incTr + $incGrp + $incPair;

            $rows[] = [
                'teacher' => $teacher,

                // для salary-table.blade.php
                'cnt_individual' => $cntInd,
                'cnt_trial'      => $cntTr,
                'cnt_group'      => $cntGrp,
                'cnt_pair'       => $cntPair,
                'salary_total'   => $salary,

                // для фінзвітної таблиці
                'rev_individual'  => $revInd,   'cost_individual' => $costInd,  'inc_individual' => $incInd,
                'rev_trial'       => $revTr,    'cost_trial'      => $costTr,   'inc_trial'      => $incTr,
                'rev_group'       => $revGrp,   'cost_group'      => $costGrp,  'inc_group'      => $incGrp,
                'rev_pair'        => $revPair,  'cost_pair'       => $costPair, 'inc_pair'       => $incPair,
                'profit_total'    => $profitTotal,
            ];

            // totals
            $totals['cnt_individual'] += $cntInd;
            $totals['cnt_trial']      += $cntTr;
            $totals['cnt_group']      += $cntGrp;
            $totals['cnt_pair']       += $cntPair;
            $totals['salary_total']   += $salary;

            $totals['rev_ind']   += $revInd;   $totals['cost_ind']  += $costInd;  $totals['inc_ind']  += $incInd;
            $totals['rev_trial'] += $revTr;    $totals['cost_trial']+= $costTr;   $totals['inc_trial'] += $incTr;
            $totals['rev_grp']   += $revGrp;   $totals['cost_grp']  += $costGrp;  $totals['inc_grp']   += $incGrp;
            $totals['rev_pair']  += $revPair;  $totals['cost_pair'] += $costPair; $totals['inc_pair']  += $incPair;
            $totals['profit_total'] += $profitTotal;
        }

        return ['rows' => $rows, 'totals' => $totals];
    }
}
