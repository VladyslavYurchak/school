<?php

namespace App\Services\Data;

use App\Models\Teacher;

class TeacherMonthlyReportService
{
    public function build(int $year, int $month): array
    {
        $out = [];
        $teachers = Teacher::query()->where('is_active', 1)->get();

        foreach ($teachers as $t) {
            $d = $t->getMonthlyIncomeDetailed($year, $month);

            $counts  = $d['counts'];   // individual, trial, group, pair
            $costs   = $d['costs'];    // individual, trial, group, pair
            $revenue = $d['revenue'];  // individual, trial, group, pair
            $income  = $d['income'];   // individual, trial, group, pair

            $out[$t->id] = [
                'teacher' => $t,

                // К-сті
                'cnt_individual' => (int)($counts['individual'] ?? 0),
                'cnt_trial'      => (int)($counts['trial'] ?? 0),
                'cnt_group'      => (int)($counts['group'] ?? 0),
                'cnt_pair'       => (int)($counts['pair'] ?? 0),

                // Собівартість (зарплата) по типах
                'cost_individual' => (float)($costs['individual'] ?? 0),
                'cost_trial'      => (float)($costs['trial'] ?? 0),
                'cost_group'      => (float)($costs['group'] ?? 0),
                'cost_pair'       => (float)($costs['pair'] ?? 0),

                // Доходи по типах
                'rev_individual' => (float)($revenue['individual'] ?? 0),
                'rev_trial'      => (float)($revenue['trial'] ?? 0),
                'rev_group'      => (float)($revenue['group'] ?? 0),
                'rev_pair'       => (float)($revenue['pair'] ?? 0),

                // Прибуток по типах
                'inc_individual' => (float)($income['individual'] ?? 0),
                'inc_trial'      => (float)($income['trial'] ?? 0),
                'inc_group'      => (float)($income['group'] ?? 0),
                'inc_pair'       => (float)($income['pair'] ?? 0),

                // Загальні агрегати для зручності
                'salary_total'   => (float)(($costs['individual'] ?? 0) + ($costs['trial'] ?? 0) + ($costs['group'] ?? 0) + ($costs['pair'] ?? 0)),
                'profit_total'   => (float)($d['total_income'] ?? 0),
            ];
        }

        return $out;
    }
}
