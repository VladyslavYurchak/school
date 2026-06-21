<?php

namespace Tests\Feature;

use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTeachersPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_teachers_page_renders_profile_text_fields(): void
    {
        Teacher::factory()->create([
            'first_name' => 'Даша',
            'last_name' => 'Юрчак',
            'is_active' => true,
            'is_public' => true,
            'public_position' => 'Викладачка англійської мови',
            'public_bio' => '<p>Перший абзац опису.</p><p><em>Другий абзац опису.</em></p>',
            'public_details' => "Досвід: 6 років\nФормат: онлайн/офлайн\nУчні: діти від 8 років/дорослі",
        ]);

        $this
            ->get(route('teachers.index'))
            ->assertOk()
            ->assertSee('Даша Юрчак')
            ->assertSee('Викладачка англійської мови')
            ->assertSee('Перший абзац опису.')
            ->assertSee('Другий абзац опису.')
            ->assertSee('<em>Другий абзац опису.</em>', false)
            ->assertSee('Досвід: 6 років')
            ->assertSee('Формат: онлайн/офлайн')
            ->assertSee('Учні: діти від 8 років/дорослі')
            ->assertSee('class="teacher-position"', false)
            ->assertSee('class="teacher-details"', false)
            ->assertSee('class="teacher-bio"', false);
    }

    public function test_public_teachers_page_hides_private_teachers(): void
    {
        Teacher::factory()->create([
            'first_name' => 'Прихований',
            'last_name' => 'Викладач',
            'is_active' => true,
            'is_public' => false,
        ]);

        $this
            ->get(route('teachers.index'))
            ->assertOk()
            ->assertDontSee('Прихований Викладач');
    }
}
