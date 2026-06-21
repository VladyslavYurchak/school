<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTeacherPagesLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_pages_use_unified_admin_layout(): void
    {
        [$user, $teacher] = $this->createTeacherUser();

        Student::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        Group::factory()->create([
            'teacher_id' => $teacher->id,
            'name' => 'A1 Group',
            'type' => 'group',
        ]);

        foreach ([
            route('admin.teacher.my_students'),
            route('admin.teacher.my_groups'),
            route('admin.teacher_income.index'),
            route('admin.calendar.index'),
        ] as $url) {
            $response = $this
                ->actingAs($user)
                ->get($url)
                ->assertOk()
                ->assertSee('class="admin-page"', false)
                ->assertSee('class="admin-hero"', false)
                ->assertSee('admin-panel', false)
                ->assertDontSee('<style>', false);

            $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
        }
    }

    public function test_guest_teacher_page_request_redirects_to_login_instead_of_forbidden(): void
    {
        $this
            ->get(route('admin.teacher.my_students'))
            ->assertRedirect(route('login'));
    }

    private function createTeacherUser(): array
    {
        $user = User::factory()->create(['role' => 'teacher']);

        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
        ]);

        return [$user, $teacher];
    }
}
