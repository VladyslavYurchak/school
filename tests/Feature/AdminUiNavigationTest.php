<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Language;
use App\Models\SchoolRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUiNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_uses_real_navigation_without_old_dead_items(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Панель керування')
            ->assertSee('Правила школи')
            ->assertSee(route('admin.school-rules.index'), false)
            ->assertDontSee('Відеоматеріали')
            ->assertDontSee('Контактні дані')
            ->assertDontSee('AdminLTE v4', false);
    }

    public function test_teacher_sidebar_does_not_show_admin_site_management_links(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this
            ->actingAs($teacher)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Кабінет викладача')
            ->assertDontSee('Правила школи')
            ->assertDontSee('Пости')
            ->assertDontSee('Дані');
    }

    public function test_admin_school_rules_page_uses_admin_management_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        SchoolRule::create([
            'title' => 'Оплата',
            'content' => '<p>Правило оплати</p>',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.school-rules.index'))
            ->assertOk()
            ->assertSee('Керуйте правилами')
            ->assertSee('Переглянути на сайті')
            ->assertSee('Активне')
            ->assertSee('Оплата');
    }
    public function test_admin_courses_page_uses_unified_admin_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $language = Language::create(['name' => 'English']);

        Course::create([
            'title' => 'A1 English',
            'description' => 'Starter course',
            'language_id' => $language->id,
            'price' => 0,
            'is_published' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.course.index'))
            ->assertOk()
            ->assertSee('class="admin-page"', false)
            ->assertSee('class="admin-hero"', false)
            ->assertSee('class="admin-panel"', false)
            ->assertSee('class="table admin-table mb-0"', false)
            ->assertSee('A1 English')
            ->assertSee('English');

        $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
    }
}
