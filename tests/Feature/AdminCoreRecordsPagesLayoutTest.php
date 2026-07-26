<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Models\SubscriptionTemplate;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCoreRecordsPagesLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_record_pages_use_unified_admin_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = Teacher::factory()->create([
            'first_name' => 'Core',
            'last_name' => 'Teacher',
        ]);
        $template = SubscriptionTemplate::factory()->create([
            'title' => 'Core Individual',
            'type' => 'individual',
            'lessons_per_week' => 2,
            'price' => 2400,
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'subscription_id' => $template->id,
            'is_active' => true,
        ]);

        Group::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'Core Group',
            'type' => 'group',
        ]);

        foreach ([
            route('admin.students.index'),
            route('admin.groups.index'),
            route('admin.subscription-templates.index'),
            route('admin.data.index'),
        ] as $url) {
            $response = $this
                ->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-page"', false)
                ->assertSee('class="admin-hero"', false)
                ->assertSee('admin-panel', false)
                ->assertDontSee('<style>', false);

            $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        }
    }

    public function test_students_page_keeps_search_form_modals_and_payment_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['is_active' => true]);

        $this
            ->actingAs($admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('id="toggle-student-form"', false)
            ->assertSee('id="studentSearch"', false)
            ->assertSee('data-bs-target="#studentModal'.$student->id.'"', false)
            ->assertSee('data-bs-target="#paymentModal'.$student->id.'"', false)
            ->assertSee('window.activeStudentIds', false);
    }

    public function test_cancelled_subscription_does_not_mark_current_month_as_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create(['is_active' => true]);

        StudentSubscription::factory()->create([
            'student_id' => $student->id,
            'status' => 'cancelled',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
            'price' => 2500,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertViewHas('paidMonthsByStudent', function ($months) use ($student) {
                return ($months[$student->id] ?? []) === [];
            })
            ->assertSee('table-danger', false);
    }

    public function test_student_edit_page_uses_unified_layout_and_single_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::factory()->create([
            'first_name' => 'Edit',
            'last_name' => 'Student',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.students.edit', $student))
            ->assertOk()
            ->assertSee('class="admin-page"', false)
            ->assertSee('class="admin-hero"', false)
            ->assertSee('admin-panel', false)
            ->assertSee(route('admin.students.update', $student), false)
            ->assertSee('name="first_name"', false)
            ->assertSee('name="subscription_id"', false)
            ->assertDontSee('<style>', false);

        $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        $this->assertSame(1, substr_count($response->getContent(), '<form'));
    }

    public function test_group_teacher_and_subscription_forms_use_unified_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacherUser = User::factory()->create(['role' => 'student']);
        $teacher = Teacher::factory()->create([
            'user_id' => $teacherUser->id,
            'first_name' => 'Layout',
            'last_name' => 'Teacher',
        ]);
        $template = SubscriptionTemplate::factory()->create([
            'type' => 'group',
            'lessons_per_week' => 2,
        ]);
        $group = Group::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'Layout Group',
            'type' => 'group',
        ]);

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'subscription_id' => $template->id,
            'group_id' => $group->id,
            'is_active' => true,
        ]);

        foreach ([
            route('admin.groups.create'),
            route('admin.groups.edit', $group),
            route('admin.teachers.create'),
            route('admin.teachers.edit', $teacher),
            route('admin.subscription-templates.create'),
        ] as $url) {
            $response = $this
                ->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-page"', false)
                ->assertSee('class="admin-hero"', false)
                ->assertSee('admin-panel', false)
                ->assertDontSee('btn btn-', false)
                ->assertDontSee('class="card', false)
                ->assertDontSee('style="', false)
                ->assertDontSee('<style>', false);

            $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        }
    }
}
