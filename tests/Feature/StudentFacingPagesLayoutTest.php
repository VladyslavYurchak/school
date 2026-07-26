<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\LessonLog;
use App\Models\Student;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFacingPagesLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_uses_account_layout_and_hides_internal_teacher_note(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $teacherUser = User::factory()->create(['role' => 'teacher']);
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
            'note' => 'Internal teacher note must stay in CRM',
        ]);

        Student::factory()->create([
            'user_id' => $user->id,
            'teacher_id' => $teacher->id,
        ]);

        LessonLog::factory()->create([
            'student_id' => Student::query()->where('user_id', $user->id)->value('id'),
            'status' => 'completed',
            'lesson_type' => 'individual',
        ]);

        $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('class="account-page"', false)
            ->assertSee('class="account-table"', false)
            ->assertSee('data-label="Дата"', false)
            ->assertDontSee('Internal teacher note must stay in CRM');
    }

    public function test_student_payment_page_uses_responsive_payment_layout(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $template = SubscriptionTemplate::factory()->create();

        Student::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $template->id,
        ]);

        $this->actingAs($user)
            ->get(route('student.payments.index'))
            ->assertOk()
            ->assertSee('class="payment-layout"', false)
            ->assertSee('class="payment-summary"', false)
            ->assertSee('class="btn-brand w-100"', false);
    }

    public function test_course_catalog_uses_public_catalog_components(): void
    {
        $language = Language::create(['name' => 'English']);
        $course = Course::create([
            'title' => 'Responsive course',
            'description' => 'Course description',
            'language_id' => $language->id,
            'price' => 500,
            'is_published' => true,
        ]);

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('class="catalog-page"', false)
            ->assertSee('class="catalog-card"', false)
            ->assertSee('Responsive course');

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('class="course-hero"', false)
            ->assertSee('class="course-lessons-list"', false)
            ->assertDontSee('<style>', false);
    }

    public function test_contact_map_is_not_wrapped_in_an_interactive_link(): void
    {
        $response = $this->get(route('contact.index'));

        $response
            ->assertOk()
            ->assertSee('title="Корпорація Мов на Google Maps"', false)
            ->assertDontSee('class="map-link"', false);
    }

    public function test_auth_pages_share_localized_auth_layout_without_inline_page_styles(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('class="auth-page"', false)
            ->assertSee('Забули пароль?')
            ->assertSee(route('social.redirect', 'google'), false)
            ->assertSee(route('social.redirect', 'facebook'), false)
            ->assertDontSee('<style>', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('class="auth-page"', false)
            ->assertSee('Підтвердження пароля')
            ->assertSee(route('social.redirect', 'google'), false)
            ->assertSee(route('social.redirect', 'facebook'), false)
            ->assertDontSee('<style>', false);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Відновлення пароля')
            ->assertSee('class="auth-card"', false);
    }
}
