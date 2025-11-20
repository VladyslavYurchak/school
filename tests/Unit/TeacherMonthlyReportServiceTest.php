<?php

namespace Tests\Unit;

use App\Models\LessonLog;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Data\TeacherMonthlyReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherMonthlyReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_aggregates_salary_costs_and_totals_correctly(): void
    {
        $year  = 2025;
        $month = 1;

        // 1. Створюємо викладача (User + Teacher)
        $user = User::factory()->create([
            'role' => 'teacher',
        ]);

        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
        ]);

        /**
         * 2. Створюємо lesson_logs для цього викладача
         *    в межах місяця та за його межами
         *
         *    Всі суми teacher_payout_amount будемо потім чекати в зарплаті/собівартості.
         */

        // 2 індивідуальні в цьому місяці
        LessonLog::factory()->create([
            'teacher_id'            => $teacher->id,
            'lesson_type'           => 'individual',
            'date'                  => "{$year}-01-05",
            'status'                => 'completed',
            'teacher_payout_amount' => 100,
        ]);

        LessonLog::factory()->create([
            'teacher_id'            => $teacher->id,
            'lesson_type'           => 'individual',
            'date'                  => "{$year}-01-10",
            'status'                => 'charged',
            'teacher_payout_amount' => 120,
        ]);

        // 1 trial у місяці
        LessonLog::factory()->create([
            'teacher_id'            => $teacher->id,
            'lesson_type'           => 'trial',
            'date'                  => "{$year}-01-12",
            'status'                => 'completed',
            'teacher_payout_amount' => 50,
        ]);

        // 1 груповий
        LessonLog::factory()->create([
            'teacher_id'            => $teacher->id,
            'lesson_type'           => 'group',
            'date'                  => "{$year}-01-15",
            'status'                => 'completed',
            'teacher_payout_amount' => 200,
        ]);

        // 1 парний
        LessonLog::factory()->create([
            'teacher_id'            => $teacher->id,
            'lesson_type'           => 'pair',
            'date'                  => "{$year}-01-18",
            'status'                => 'completed',
            'teacher_payout_amount' => 150,
        ]);

        // Урок поза місяцем — НЕ має увійти в звіт
        LessonLog::factory()->create([
            'teacher_id'            => $teacher->id,
            'lesson_type'           => 'individual',
            'date'                  => "{$year}-02-01",
            'status'                => 'completed',
            'teacher_payout_amount' => 999,
        ]);

        /**
         * 3. Викликаємо сервіс
         */
        $service = new TeacherMonthlyReportService();
        $result  = $service->build($year, $month);

        $rows   = $result['rows'];
        $totals = $result['totals'];

        // Має бути рівно один рядок — наш викладач
        $this->assertCount(1, $rows);
        $row = $rows[0];

        // 4. Перевіряємо зарплату (salary_total) і собівартість по типах

        // salary_total = сума teacher_payout_amount в місяці:
        // 100 + 120 + 50 + 200 + 150 = 620
        $this->assertEquals(620.0, $row['salary_total']);

        // cost_individual = 100 + 120
        $this->assertEquals(220.0, $row['cost_individual']);

        // cost_trial = 50
        $this->assertEquals(50.0, $row['cost_trial']);

        // cost_group = 200
        $this->assertEquals(200.0, $row['cost_group']);

        // cost_pair = 150
        $this->assertEquals(150.0, $row['cost_pair']);

        // 5. Перевіряємо, що інкременти по типах рахуються як rev - cost

        // Ми не знаємо точне значення rev_* (це логіка Teacher),
        // але можемо перевірити звʼязок: inc = rev - cost

        $this->assertEquals(
            $row['rev_individual'] - $row['cost_individual'],
            $row['inc_individual']
        );

        $this->assertEquals(
            $row['rev_trial'] - $row['cost_trial'],
            $row['inc_trial']
        );

        $this->assertEquals(
            $row['rev_group'] - $row['cost_group'],
            $row['inc_group']
        );

        $this->assertEquals(
            $row['rev_pair'] - $row['cost_pair'],
            $row['inc_pair']
        );

        // Перевіряємо, що profit_total = сума всіх inc_*
        $expectedProfit = $row['inc_individual']
            + $row['inc_trial']
            + $row['inc_group']
            + $row['inc_pair'];

        $this->assertEquals($expectedProfit, $row['profit_total']);

        // 6. Перевіряємо totals: з одним викладачем вони просто мають дорівнювати рядку

        // Кількості
        $this->assertEquals($row['cnt_individual'], $totals['cnt_individual']);
        $this->assertEquals($row['cnt_trial'],      $totals['cnt_trial']);
        $this->assertEquals($row['cnt_group'],      $totals['cnt_group']);
        $this->assertEquals($row['cnt_pair'],       $totals['cnt_pair']);

        // Зарплата
        $this->assertEquals($row['salary_total'], $totals['salary_total']);

        // Rev / cost / inc по кожному типу
        $this->assertEquals($row['rev_individual'],  $totals['rev_ind']);
        $this->assertEquals($row['cost_individual'], $totals['cost_ind']);
        $this->assertEquals($row['inc_individual'],  $totals['inc_ind']);

        $this->assertEquals($row['rev_trial'],  $totals['rev_trial']);
        $this->assertEquals($row['cost_trial'], $totals['cost_trial']);
        $this->assertEquals($row['inc_trial'],  $totals['inc_trial']);

        $this->assertEquals($row['rev_group'],  $totals['rev_grp']);
        $this->assertEquals($row['cost_group'], $totals['cost_grp']);
        $this->assertEquals($row['inc_group'],  $totals['inc_grp']);

        $this->assertEquals($row['rev_pair'],  $totals['rev_pair']);
        $this->assertEquals($row['cost_pair'], $totals['cost_pair']);
        $this->assertEquals($row['inc_pair'],  $totals['inc_pair']);

        // Загальний прибуток
        $this->assertEquals($row['profit_total'], $totals['profit_total']);
    }
}
