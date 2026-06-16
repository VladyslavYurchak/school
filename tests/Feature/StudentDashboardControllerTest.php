<?php

namespace Tests\Feature;

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

        $this
            ->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Dev Individual')
            ->assertSee('Активний')
            ->assertSee('01.06.2026')
            ->assertDontSee('Всього занять')
            ->assertDontSee('Використано')
            ->assertDontSee('Залишилось');
    }
}
