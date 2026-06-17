<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\LessonLog;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardControllerTest extends TestCase
{
    use RefreshDatabase;

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
}
