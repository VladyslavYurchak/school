<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Group;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonLog;
use App\Models\PlannedLesson;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_student_account_sees_pending_profile_instead_of_not_found(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'email' => 'new-student@example.com',
        ]);

        $this
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewIs('student.pending-profile')
            ->assertSee('Акаунт успішно створено')
            ->assertSee('new-student@example.com')
            ->assertSee(route('courses.index'), false);
    }

    public function test_dashboard_shows_only_students_upcoming_planned_lessons_in_chronological_order(): void
    {
        Carbon::setTestNow('2026-07-26 12:00:00');

        $user = User::factory()->create(['role' => 'student']);
        $teacher = Teacher::factory()->create([
            'first_name' => 'Olena',
            'last_name' => 'Teacher',
        ]);
        $group = Group::factory()->group()->create([
            'teacher_id' => $teacher->id,
            'name' => 'A2 Group',
        ]);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
        ]);
        $otherStudent = Student::factory()->create();
        $otherGroup = Group::factory()->group()->create();

        $groupLesson = PlannedLesson::factory()->group()->create([
            'title' => 'Visible group lesson',
            'teacher_id' => $teacher->id,
            'group_id' => $group->id,
            'start_date' => '2026-07-27 09:00:00',
            'end_date' => '2026-07-27 10:00:00',
            'status' => 'planned',
        ]);
        $individualLesson = PlannedLesson::factory()->individual()->create([
            'title' => 'Visible individual lesson',
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'start_date' => '2026-07-26 16:30:00',
            'end_date' => '2026-07-26 17:30:00',
            'status' => 'planned',
        ]);

        PlannedLesson::factory()->individual()->create([
            'title' => 'Hidden foreign student lesson',
            'student_id' => $otherStudent->id,
            'start_date' => '2026-07-26 14:00:00',
            'status' => 'planned',
        ]);
        PlannedLesson::factory()->group()->create([
            'title' => 'Hidden foreign group lesson',
            'group_id' => $otherGroup->id,
            'start_date' => '2026-07-26 15:00:00',
            'status' => 'planned',
        ]);
        PlannedLesson::factory()->individual()->create([
            'title' => 'Hidden past lesson',
            'student_id' => $student->id,
            'start_date' => '2026-07-26 11:00:00',
            'status' => 'planned',
        ]);
        PlannedLesson::factory()->individual()->create([
            'title' => 'Hidden cancelled lesson',
            'student_id' => $student->id,
            'start_date' => '2026-07-27 12:00:00',
            'status' => 'cancelled',
        ]);
        $deletedLesson = PlannedLesson::factory()->individual()->create([
            'title' => 'Hidden deleted lesson',
            'student_id' => $student->id,
            'start_date' => '2026-07-28 12:00:00',
            'status' => 'planned',
        ]);
        $deletedLesson->delete();

        $this
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertViewHas('upcomingLessons', function ($lessons) use ($individualLesson, $groupLesson) {
                return $lessons->pluck('id')->all() === [
                    $individualLesson->id,
                    $groupLesson->id,
                ];
            })
            ->assertSee('Visible individual lesson')
            ->assertSee('Visible group lesson')
            ->assertDontSee('Hidden foreign student lesson')
            ->assertDontSee('Hidden foreign group lesson')
            ->assertDontSee('Hidden past lesson')
            ->assertDontSee('Hidden cancelled lesson')
            ->assertDontSee('Hidden deleted lesson');

        Carbon::setTestNow();
    }

    public function test_dashboard_shows_subscription_without_unreliable_lesson_counters(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create([
            'title' => 'Dev Individual',
            'type' => 'individual',
            'lessons_per_week' => 2,
            'price' => 2800,
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'subscription_template_id' => $template->id,
            'type' => 'subscription',
            'status' => 'active',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'lessons_total' => 8,
            'lessons_used' => 1,
            'price' => 2800,
        ]);

        LessonLog::factory()->create([
            'student_id' => $student->id,
            'lesson_type' => 'individual',
            'status' => 'completed',
            'date' => '2026-06-10',
        ]);

        $this
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Dev Individual')
            ->assertSee('Активний')
            ->assertSee('Продовжити абонемент')
            ->assertSee('01.06.2026')
            ->assertSee('Проведено')
            ->assertDontSee('completed')
            ->assertDontSee('Всього занять')
            ->assertDontSee('Використано')
            ->assertDontSee('Залишилось');
    }

    public function test_dashboard_shows_paid_separate_lessons(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Student::factory()->create(['user_id' => $user->id]);

        $language = Language::create(['name' => 'English']);
        $course = Course::create([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 1000,
            'is_published' => true,
        ]);

        $paidLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Paid separate lesson',
            'description' => 'Lesson description',
            'position' => 1,
            'price' => 300,
            'is_published' => true,
        ]);

        $unpaidLesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Unpaid lesson',
            'description' => 'Lesson description',
            'position' => 2,
            'price' => 300,
            'is_published' => true,
        ]);

        $user->lessons()->attach($paidLesson->id, [
            'status' => 'paid',
            'paid_amount' => 300,
        ]);

        $this
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Мої окремі уроки')
            ->assertSee('Paid separate lesson')
            ->assertSee(route('courses.lessons.show', [$course, $paidLesson]), false)
            ->assertDontSee('Unpaid lesson');
    }

    public function test_dashboard_shows_only_paid_course_and_lesson_access(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        Student::factory()->create(['user_id' => $user->id]);
        $language = Language::create(['name' => 'English']);

        $paidCourse = Course::create([
            'title' => 'Visible paid course',
            'description' => 'Course',
            'language_id' => $language->id,
            'price' => 1000,
            'is_published' => true,
        ]);
        $refundedCourse = Course::create([
            'title' => 'Hidden refunded course',
            'description' => 'Course',
            'language_id' => $language->id,
            'price' => 1000,
            'is_published' => true,
        ]);
        $refundedLesson = Lesson::create([
            'course_id' => $paidCourse->id,
            'title' => 'Hidden refunded lesson',
            'description' => 'Lesson',
            'position' => 1,
            'price' => 300,
            'is_published' => true,
        ]);

        $user->courses()->attach($paidCourse->id, [
            'status' => 'paid',
            'paid_amount' => 1000,
        ]);
        $user->courses()->attach($refundedCourse->id, [
            'status' => 'refunded',
            'paid_amount' => 1000,
        ]);
        $user->lessons()->attach($refundedLesson->id, [
            'status' => 'refunded',
            'paid_amount' => 300,
        ]);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Visible paid course')
            ->assertDontSee('Hidden refunded course')
            ->assertDontSee('Hidden refunded lesson');
    }
}
