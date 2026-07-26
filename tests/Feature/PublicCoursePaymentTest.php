<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\Lesson;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCoursePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_course_index_lists_only_published_courses_and_counts_published_lessons(): void
    {
        $publishedCourse = $this->createCourse([
            'title' => 'Published course',
            'is_published' => true,
            'price' => 500,
        ]);
        $draftCourse = $this->createCourse([
            'title' => 'Draft course',
            'is_published' => false,
        ]);

        $this->createLesson($publishedCourse, [
            'title' => 'Published lesson',
            'is_published' => true,
        ]);
        $this->createLesson($publishedCourse, [
            'title' => 'Draft lesson',
            'is_published' => false,
        ]);

        $this
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Published course')
            ->assertSee('Уроків: 1')
            ->assertDontSee('Draft course')
            ->assertDontSee('Draft lesson');

        $this->assertSame(0, Course::query()->whereKey($draftCourse)->where('is_published', true)->count());
    }

    public function test_paid_course_page_shows_course_buy_and_separate_lesson_buy_buttons(): void
    {
        $course = $this->createCourse([
            'title' => 'Paid course',
            'price' => 1000,
            'is_published' => true,
        ]);
        $includedLesson = $this->createLesson($course, [
            'title' => 'Course-only lesson',
            'price' => null,
            'is_published' => true,
        ]);
        $separateLesson = $this->createLesson($course, [
            'title' => 'Separate lesson',
            'price' => 300,
            'is_published' => true,
        ]);
        $draftLesson = $this->createLesson($course, [
            'title' => 'Draft lesson',
            'price' => 100,
            'is_published' => false,
        ]);

        $user = User::factory()->create(['role' => 'student']);

        $this
            ->actingAs($user)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Придбати весь курс')
            ->assertSee($includedLesson->title)
            ->assertSee($separateLesson->title)
            ->assertSee('300 грн')
            ->assertDontSee($draftLesson->title);
    }

    public function test_course_purchase_creates_pending_single_payment(): void
    {
        $course = $this->createCourse([
            'title' => 'Paid course',
            'price' => 1000,
            'is_published' => true,
        ]);
        $user = User::factory()->create(['role' => 'student']);

        $response = $this
            ->actingAs($user)
            ->post(route('courses.buy', $course));

        $payment = Payment::firstOrFail();

        $response->assertRedirect(route('student.payments.checkout', $payment));
        $this->assertSame('single', $payment->type);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('monopay', $payment->provider);
        $this->assertEquals(1000, (float) $payment->amount);
        $this->assertSame('Оплата за "Paid course"', $payment->description);
        $this->assertSame($course->id, $payment->payload['course_id']);
        $this->assertSame($user->id, $payment->payload['user_id']);
    }

    public function test_lesson_purchase_creates_pending_single_payment(): void
    {
        $course = $this->createCourse([
            'price' => 1000,
            'is_published' => true,
        ]);
        $lesson = $this->createLesson($course, [
            'title' => 'Separate lesson',
            'price' => 300,
            'is_published' => true,
        ]);
        $user = User::factory()->create(['role' => 'student']);

        $response = $this
            ->actingAs($user)
            ->post(route('lessons.buy', $lesson));

        $payment = Payment::firstOrFail();

        $response->assertRedirect(route('student.payments.checkout', $payment));
        $this->assertSame('single', $payment->type);
        $this->assertSame('pending', $payment->status);
        $this->assertEquals(300, (float) $payment->amount);
        $this->assertSame('Оплата за "Separate lesson"', $payment->description);
        $this->assertSame($lesson->id, $payment->payload['lesson_id']);
        $this->assertSame($user->id, $payment->payload['user_id']);
    }

    public function test_lesson_purchase_is_blocked_when_course_is_unpublished(): void
    {
        $course = $this->createCourse([
            'price' => 1000,
            'is_published' => false,
        ]);
        $lesson = $this->createLesson($course, [
            'price' => 300,
            'is_published' => true,
        ]);
        $user = User::factory()->create(['role' => 'student']);

        $this
            ->actingAs($user)
            ->post(route('lessons.buy', $lesson))
            ->assertNotFound();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_repeated_course_purchase_reuses_pending_payment(): void
    {
        $course = $this->createCourse([
            'price' => 1000,
            'is_published' => true,
        ]);
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->post(route('courses.buy', $course));
        $payment = Payment::firstOrFail();

        $this->actingAs($user)
            ->post(route('courses.buy', $course))
            ->assertRedirect(route('student.payments.checkout', $payment));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_repeated_lesson_purchase_reuses_pending_payment(): void
    {
        $course = $this->createCourse([
            'price' => 1000,
            'is_published' => true,
        ]);
        $lesson = $this->createLesson($course, [
            'price' => 300,
            'is_published' => true,
        ]);
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->post(route('lessons.buy', $lesson));
        $payment = Payment::firstOrFail();

        $this->actingAs($user)
            ->post(route('lessons.buy', $lesson))
            ->assertRedirect(route('student.payments.checkout', $payment));

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_expired_course_invoice_is_failed_and_replaced(): void
    {
        $course = $this->createCourse(['price' => 1000]);
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->post(route('courses.buy', $course));
        $oldPayment = Payment::firstOrFail();
        $oldPayment->forceFill([
            'provider_payment_id' => 'expired-course-invoice',
            'payload' => array_merge($oldPayment->payload, [
                'mono_invoice' => [
                    'invoiceId' => 'expired-course-invoice',
                    'pageUrl' => 'https://pay.example/expired-course',
                ],
            ]),
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ])->save();

        $response = $this->actingAs($user)->post(route('courses.buy', $course));
        $newPayment = Payment::query()->whereKeyNot($oldPayment->id)->firstOrFail();

        $response->assertRedirect(route('student.payments.checkout', $newPayment));
        $this->assertSame('failed', $oldPayment->fresh()->status);
        $this->assertTrue($oldPayment->fresh()->payload['expired_locally']);
        $this->assertSame('pending', $newPayment->status);
    }

    public function test_expired_lesson_invoice_is_failed_and_replaced(): void
    {
        $course = $this->createCourse(['price' => 1000]);
        $lesson = $this->createLesson($course, ['price' => 300]);
        $user = User::factory()->create(['role' => 'student']);

        $this->actingAs($user)->post(route('lessons.buy', $lesson));
        $oldPayment = Payment::firstOrFail();
        $oldPayment->forceFill([
            'provider_payment_id' => 'expired-lesson-invoice',
            'payload' => array_merge($oldPayment->payload, [
                'mono_invoice' => [
                    'invoiceId' => 'expired-lesson-invoice',
                    'pageUrl' => 'https://pay.example/expired-lesson',
                ],
            ]),
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ])->save();

        $response = $this->actingAs($user)->post(route('lessons.buy', $lesson));
        $newPayment = Payment::query()->whereKeyNot($oldPayment->id)->firstOrFail();

        $response->assertRedirect(route('student.payments.checkout', $newPayment));
        $this->assertSame('failed', $oldPayment->fresh()->status);
        $this->assertTrue($oldPayment->fresh()->payload['expired_locally']);
        $this->assertSame('pending', $newPayment->status);
    }

    private function createCourse(array $attributes = []): Course
    {
        $language = Language::create(['name' => 'English']);

        return Course::create(array_merge([
            'title' => 'English A1',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 0,
            'is_published' => true,
        ], $attributes));
    }

    private function createLesson(Course $course, array $attributes = []): Lesson
    {
        return Lesson::create(array_merge([
            'course_id' => $course->id,
            'title' => 'Lesson',
            'description' => 'Lesson description',
            'lesson_type' => 'Reading',
            'position' => 1,
            'price' => null,
            'is_published' => true,
        ], $attributes));
    }
}
