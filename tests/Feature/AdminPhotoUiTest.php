<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPhotoUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_standardized_carousel_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $source = UploadedFile::fake()->image('carousel.jpg', 1600, 1000);
        $dataUrl = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($source->getRealPath()));

        $this
            ->actingAs($admin)
            ->post(route('admin.photos.upload'), ['cropped_image' => $dataUrl])
            ->assertRedirect(route('admin.photos.index'));

        $photo = Photo::query()->firstOrFail();

        $this->assertStringEndsWith('.webp', $photo->path);
        Storage::disk('public')->assertExists($photo->path);

        [$width, $height, $type] = getimagesize(Storage::disk('public')->path($photo->path));

        $this->assertSame(1200, $width);
        $this->assertSame(1200, $height);
        $this->assertSame(IMAGETYPE_WEBP, $type);
    }

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
