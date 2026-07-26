<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaidContentProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_paid_course_cannot_be_deleted_and_ui_recommends_unpublishing(): void
    {
        [$course] = $this->courseWithLesson();
        $studentUser = User::factory()->create(['role' => 'student']);
        $studentUser->courses()->attach($course->id, [
            'status' => 'paid',
            'paid_amount' => 900,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.course.delete', $course))
            ->assertRedirect(route('admin.course.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);

        $this->actingAs($this->admin)
            ->get(route('admin.course.index'))
            ->assertOk()
            ->assertSee('Є оплати')
            ->assertDontSee(
                'action="'.route('admin.course.delete', $course).'"',
                false
            );
    }

    public function test_course_cannot_be_deleted_while_one_of_its_lessons_has_pending_payment(): void
    {
        [$course, $lesson] = $this->courseWithLesson();
        $student = Student::factory()->create();

        Payment::create([
            'student_id' => $student->id,
            'amount' => 300,
            'currency' => 'UAH',
            'status' => 'pending',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'pending-lesson-order',
            'payload' => ['lesson_id' => $lesson->id],
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.course.delete', $course))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
    }

    public function test_paid_lesson_cannot_be_deleted_but_can_be_unpublished(): void
    {
        [$course, $lesson] = $this->courseWithLesson();
        $studentUser = User::factory()->create(['role' => 'student']);
        $studentUser->lessons()->attach($lesson->id, [
            'status' => 'paid',
            'paid_amount' => 300,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.delete', $lesson))
            ->assertRedirect(route('admin.course.show', $course))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.course.lesson.update', $lesson), [
                'lesson_type' => $lesson->lesson_type,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'price' => $lesson->price,
                'is_published' => '0',
            ])
            ->assertRedirect(route('admin.course.show', $course));

        $this->assertFalse($lesson->fresh()->is_published);
    }

    public function test_refunded_purchase_history_still_protects_lesson_from_deletion(): void
    {
        [$course, $lesson] = $this->courseWithLesson();
        $student = Student::factory()->create();

        Payment::create([
            'student_id' => $student->id,
            'amount' => 300,
            'currency' => 'UAH',
            'status' => 'refunded',
            'type' => 'single',
            'provider' => 'monopay',
            'provider_order_id' => 'refunded-lesson-order',
            'payload' => ['lesson_id' => $lesson->id],
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.course.lesson.delete', $lesson))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
    }

    private function courseWithLesson(): array
    {
        $course = Course::create([
            'title' => 'Paid course',
            'description' => 'Course',
            'language_id' => Language::create(['name' => 'English'])->id,
            'price' => 900,
            'is_published' => true,
        ]);
        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => 'Paid lesson',
            'description' => 'Lesson',
            'lesson_type' => 'Reading',
            'position' => 1,
            'price' => 300,
            'is_published' => true,
        ]);

        return [$course, $lesson];
    }
}
