<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPostUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_post_index_uses_admin_page_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Post::create([
            'title' => 'School news',
            'content' => 'Post content',
            'image' => '',
            'is_published' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.post.index'))
            ->assertOk()
            ->assertSee('class="admin-page"', false)
            ->assertSee('School news')
            ->assertSee('Опубліковано');

        $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
    }

    public function test_admin_post_form_can_create_draft_without_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->post(route('admin.post.store'), [
                'title' => 'Draft news',
                'content' => 'Draft content',
                'is_published' => 0,
            ])
            ->assertRedirect(route('admin.post.index'));

        $this->assertDatabaseHas('posts', [
            'title' => 'Draft news',
            'content' => 'Draft content',
            'image' => '',
            'is_published' => false,
        ]);
    }

    public function test_admin_post_form_can_upload_image_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->post(route('admin.post.store'), [
                'title' => 'Post with upload',
                'content' => 'Post content',
                'image' => 'https://example.com/old.jpg',
                'image_file' => UploadedFile::fake()->image('post.jpg'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.post.index'));

        $post = Post::where('title', 'Post with upload')->firstOrFail();

        $this->assertStringStartsWith('posts/', $post->image);
        Storage::disk('public')->assertExists($post->image);
    }

    public function test_admin_post_form_rejects_title_longer_than_database_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->from(route('admin.post.create'))
            ->post(route('admin.post.store'), [
                'title' => str_repeat('A', 256),
                'content' => 'Draft content',
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.post.create'))
            ->assertSessionHasErrors('title');
    }

    public function test_admin_post_edit_form_updates_publish_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::create([
            'title' => 'Old title',
            'content' => 'Old content',
            'image' => '',
            'is_published' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.post.edit', $post))
            ->assertOk()
            ->assertSee('name="is_published"', false)
            ->assertSee('name="image_file"', false);

        $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));

        $this
            ->actingAs($admin)
            ->patch(route('admin.post.update', $post), [
                'title' => 'Updated title',
                'content' => 'Updated content',
                'image' => '',
                'is_published' => 0,
            ])
            ->assertRedirect(route('admin.post.show', $post));

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated title',
            'is_published' => false,
        ]);
    }

    public function test_admin_post_update_can_replace_image_with_uploaded_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::create([
            'title' => 'Old title',
            'content' => 'Old content',
            'image' => 'https://example.com/old.jpg',
            'is_published' => true,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.post.update', $post), [
                'title' => 'Updated title',
                'content' => 'Updated content',
                'image' => $post->image,
                'image_file' => UploadedFile::fake()->image('updated.jpg'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.post.show', $post));

        $post->refresh();

        $this->assertStringStartsWith('posts/', $post->image);
        Storage::disk('public')->assertExists($post->image);
    }

    public function test_admin_post_update_rejects_title_longer_than_database_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::create([
            'title' => 'Old title',
            'content' => 'Old content',
            'image' => '',
            'is_published' => true,
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.post.edit', $post))
            ->patch(route('admin.post.update', $post), [
                'title' => str_repeat('A', 256),
                'content' => 'Updated content',
                'image' => '',
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.post.edit', $post))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Old title',
        ]);
    }
}
