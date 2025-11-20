<?php

namespace Tests\Feature;

use App\Models\LessonLog;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // щоб не заважав middleware 'teacher'
        $this->withoutMiddleware();
    }

    public function test_data_index_counts_are_calculated_correctly(): void
    {
        $year  = 2025;
        $month = 1;

        // Викладач + юзер
        $teacherUser = User::factory()->create([
            'role' => 'teacher',
        ]);
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
        ]);

        // Студенти
        $studentA = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        $studentB = Student::factory()->create([
            'teacher_id' => $teacher->id,
        ]);

        /**
         * УРОКИ ДЛЯ studentA:
         *  - 2 уроки в обраному місяці (completed/charged)
         *  - 1 урок поза місяцем
         *  - 1 cancel, який не враховується
         */
        LessonLog::factory()->create([
            'student_id' => $studentA->id,
            'teacher_id' => $teacher->id,
            'date'       => "{$year}-01-05",
            'status'     => 'completed',
        ]);

        LessonLog::factory()->create([
            'student_id' => $studentA->id,
            'teacher_id' => $teacher->id,
            'date'       => "{$year}-01-20",
            'status'     => 'charged',
        ]);

        // поза місяцем, але рахується в totalLessonsCount
        LessonLog::factory()->create([
            'student_id' => $studentA->id,
            'teacher_id' => $teacher->id,
            'date'       => "{$year}-02-01",
            'status'     => 'completed',
        ]);

        // cancel — не має потрапити ні в total, ні в month
        LessonLog::factory()->create([
            'student_id' => $studentA->id,
            'teacher_id' => $teacher->id,
            'date'       => "{$year}-01-10",
            'status'     => 'cancelled',
        ]);

        /**
         * УРОКИ ДЛЯ studentB:
         *  - 1 урок в обраному місяці
         */
        LessonLog::factory()->create([
            'student_id' => $studentB->id,
            'teacher_id' => $teacher->id,
            'date'       => "{$year}-01-15",
            'status'     => 'completed',
        ]);

        /**
         * ПОРАЗОВІ ОПЛАТИ (StudentSubscription без шаблону)
         *  - у studentA: 2 поразові
         *  - у studentB: 1 поразова, 1 абонемент (з шаблоном)
         */
        StudentSubscription::factory()->single()->create([
            'student_id' => $studentA->id,
        ]);

        StudentSubscription::factory()->single()->create([
            'student_id' => $studentA->id,
        ]);

        StudentSubscription::factory()->single()->create([
            'student_id' => $studentB->id,
        ]);

        // абонемент, не рахується як single
        StudentSubscription::factory()->create([
            'student_id' => $studentB->id,
        ]);

        /**
         * ПРОБНІ УРОКИ за місяць:
         *  - всі з student_id = null (як у тебе в реалі)
         */
        LessonLog::factory()->create([
            'student_id'              => null,
            'teacher_id'              => $teacher->id,
            'date'                    => "{$year}-01-07",
            'status'                  => 'completed',
            'lesson_type'             => 'trial',
            'teacher_payout_amount'   => 100,
        ]);

        LessonLog::factory()->create([
            'student_id'              => null,
            'teacher_id'              => $teacher->id,
            'date'                    => "{$year}-01-10",
            'status'                  => 'charged',
            'lesson_type'             => 'trial',
            'teacher_payout_amount'   => 150,
        ]);

        LessonLog::factory()->create([
            'student_id'              => null,
            'teacher_id'              => $teacher->id,
            'date'                    => "{$year}-01-12",
            'status'                  => 'completed',
            'lesson_type'             => 'trial',
            'teacher_payout_amount'   => 200,
        ]);

        // пробне поза місяцем — не має увійти в trialCountsByStudent
        LessonLog::factory()->create([
            'student_id'              => null,
            'teacher_id'              => $teacher->id,
            'date'                    => "{$year}-02-01",
            'status'                  => 'completed',
            'lesson_type'             => 'trial',
            'teacher_payout_amount'   => 999,
        ]);

        // Запит на сторінку
        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.data.index', [
                'month' => $month,
                'year'  => $year,
            ]));

        $response->assertOk();
        $response->assertViewIs('admin.data.index');

        // === totalLessonsCount ===
        $response->assertViewHas('totalLessonsCount', function ($totalLessonsCount) use ($studentA, $studentB) {
            // studentA: 3 уроки (2 в січні + 1 в лютому, trial-и з null не враховуються)
            // studentB: 1 урок в січні
            return
                $totalLessonsCount[$studentA->id] === 3 &&
                $totalLessonsCount[$studentB->id] === 1;
        });

        // === monthLessonsCount ===
        $response->assertViewHas('monthLessonsCount', function ($monthLessonsCount) use ($studentA, $studentB) {
            // studentA: 2 уроки в січні
            // studentB: 1 урок в січні
            return
                $monthLessonsCount[$studentA->id] === 2 &&
                $monthLessonsCount[$studentB->id] === 1;
        });

        // === singlePaymentsCount ===
        $response->assertViewHas('singlePaymentsCount', function ($singlePaymentsCount) use ($studentA, $studentB) {
            // studentA: 2 поразові
            // studentB: 1 поразова
            return
                $singlePaymentsCount[$studentA->id] === 2 &&
                $singlePaymentsCount[$studentB->id] === 1;
        });

        // === trialCountsByStudent ===
        $response->assertViewHas('trialCountsByStudent', function ($trialCountsByStudent) {
            // всі trial-и в цьому місяці лягають під ключ null
            return
                isset($trialCountsByStudent[null]) &&
                $trialCountsByStudent[null] === 3;
        });

        // === trialCostsByStudent ===
        $response->assertViewHas('trialCostsByStudent', function ($trialCostsByStudent) {
            // сума виплат по trial-уроках у цьому місяці: 100 + 150 + 200 = 450
            return
                isset($trialCostsByStudent[null]) &&
                (int) $trialCostsByStudent[null] === 450;
        });

        $response->assertViewHas('reports');
        $response->assertViewHas('reportTotals');
    }
}
