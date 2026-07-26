<?php

namespace Tests\Feature;

use App\Models\LessonLog;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
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

        $response->assertViewHas('reports');
        $response->assertViewHas('reportTotals');
    }

    public function test_data_index_shows_separate_school_and_online_payment_lists_for_selected_period(): void
    {
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);
        $student = Student::factory()->create(['teacher_id' => $teacher->id]);

        $template = SubscriptionTemplate::factory()->create([
            'type' => 'individual',
            'price' => 3000,
        ]);

        $schoolPayment = StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'active',
            'price' => 3000,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'paid_at' => '2026-06-01 10:00:00',
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'cancelled',
            'price' => 9999,
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'paid_at' => '2026-06-01 10:00:00',
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'active',
            'price' => 7777,
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
            'paid_at' => '2026-07-01 10:00:00',
        ]);

        $coursePayment = Payment::create([
            'student_id' => $student->id,
            'amount' => 900,
            'currency' => 'UAH',
            'status' => 'paid',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'course-order',
            'description' => 'Course payment',
            'paid_at' => '2026-06-05 12:00:00',
            'payload' => ['course_id' => 10],
        ]);

        Payment::create([
            'student_id' => $student->id,
            'amount' => 500,
            'currency' => 'UAH',
            'status' => 'failed',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'failed-order',
            'paid_at' => '2026-06-05 12:00:00',
            'payload' => ['lesson_id' => 20],
        ]);

        Payment::create([
            'student_id' => $student->id,
            'amount' => 1200,
            'currency' => 'UAH',
            'status' => 'paid',
            'type' => 'subscription',
            'provider' => 'monopay',
            'provider_order_id' => 'subscription-order',
            'paid_at' => '2026-06-05 12:00:00',
            'payload' => ['subscription_template_id' => $template->id],
        ]);

        $response = $this
            ->actingAs($teacherUser)
            ->get(route('admin.data.index', [
                'month' => 6,
                'year' => 2026,
            ]));

        $response->assertOk();

        $response->assertViewHas('schoolPayments', function ($payments) use ($schoolPayment) {
            return $payments->pluck('id')->all() === [$schoolPayment->id];
        });

        $response->assertViewHas('schoolPaymentsTotal', 3000.0);

        $response->assertViewHas('onlineProductPayments', function ($payments) use ($coursePayment) {
            return $payments->pluck('id')->all() === [$coursePayment->id];
        });

        $response->assertViewHas('onlineProductPaymentsTotal', 900.0);
    }

    public function test_data_index_uses_historical_subscription_and_keeps_inactive_students_with_month_activity(): void
    {
        $oldTeacher = Teacher::factory()->create([
            'user_id' => User::factory()->create(['role' => 'teacher'])->id,
        ]);
        $newTeacher = Teacher::factory()->create([
            'user_id' => User::factory()->create(['role' => 'teacher'])->id,
        ]);
        $oldTemplate = SubscriptionTemplate::factory()->create([
            'title' => 'Old monthly plan',
            'type' => 'individual',
        ]);
        $newTemplate = SubscriptionTemplate::factory()->create([
            'title' => 'New monthly plan',
            'type' => 'group',
        ]);

        $student = Student::factory()->create([
            'teacher_id' => $oldTeacher->id,
            'subscription_id' => $oldTemplate->id,
            'is_active' => true,
        ]);

        $oldSubscription = StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $oldTemplate->id,
            'status' => 'active',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]);

        $oldTemplate->update([
            'title' => 'Renamed template',
            'type' => 'pair',
            'lessons_per_week' => 4,
        ]);

        $student->update([
            'teacher_id' => $newTeacher->id,
            'subscription_id' => $newTemplate->id,
            'is_active' => false,
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $newTemplate->id,
            'status' => 'active',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-31',
        ]);

        LessonLog::factory()->create([
            'student_id' => $student->id,
            'teacher_id' => $oldTeacher->id,
            'status' => 'completed',
            'date' => '2026-06-15',
        ]);

        $inactiveWithoutActivity = Student::factory()->create([
            'teacher_id' => $newTeacher->id,
            'is_active' => false,
        ]);

        $response = $this->get(route('admin.data.index', [
            'month' => 6,
            'year' => 2026,
        ]));

        $response->assertOk();
        $response->assertViewHas('students', function ($students) use ($student, $inactiveWithoutActivity) {
            return $students->contains('id', $student->id)
                && !$students->contains('id', $inactiveWithoutActivity->id);
        });
        $response->assertViewHas('monthlySubscriptions', function ($subscriptions) use ($student, $oldSubscription, $oldTeacher) {
            $subscription = $subscriptions->get($student->id);

            return $subscription?->is($oldSubscription)
                && $subscription->teacher_id === $oldTeacher->id
                && $subscription->subscription_title === 'Old monthly plan'
                && $subscription->lesson_type === 'individual';
        });
    }

    public function test_data_index_rejects_invalid_period_values(): void
    {
        $this->from(route('admin.data.index'))
            ->get(route('admin.data.index', ['month' => 13, 'year' => 2021]))
            ->assertRedirect(route('admin.data.index'))
            ->assertSessionHasErrors(['month', 'year']);
    }

    public function test_data_index_allows_selecting_next_year(): void
    {
        $nextYear = now()->year + 1;

        $this->get(route('admin.data.index', [
            'month' => 1,
            'year' => $nextYear,
        ]))
            ->assertOk()
            ->assertViewHas('selectedYear', $nextYear)
            ->assertSee((string) $nextYear);
    }
}
