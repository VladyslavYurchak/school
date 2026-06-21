<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPhotoUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_photo_page_uses_responsive_admin_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Photo::create(['path' => 'photos/school.webp']);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.photos.index'))
            ->assertOk()
            ->assertSee('class="admin-page"', false)
            ->assertSee('id="photo-upload-form"', false)
            ->assertSee('class="admin-photo-grid"', false)
            ->assertSee('photos/school.webp');

        $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
    }

    public function test_admin_photo_page_has_empty_state(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->get(route('admin.photos.index'))
            ->assertOk()
            ->assertSee('Фото поки немає')
            ->assertSee('Завантажте перше фото');
    }
}
