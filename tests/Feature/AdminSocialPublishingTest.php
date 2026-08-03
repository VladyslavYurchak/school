<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\SocialPublication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSocialPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_open_social_publishing_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($admin)
            ->get(route('admin.social-publishing.index'))
            ->assertOk()
            ->assertSee('Соцмережі')
            ->assertSee('Безпечний тестовий режим активний.');

        $this->actingAs($teacher)
            ->get(route('admin.social-publishing.index'))
            ->assertRedirect(route('index'));
    }

    public function test_admin_sidebar_contains_separate_social_publishing_link(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.social-publishing.index'))
            ->assertOk()
            ->assertSee(route('admin.social-publishing.index'), false)
            ->assertSee('Соцмережі');
    }

    public function test_admin_can_create_an_isolated_draft_with_media_and_targets(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('admin.social-publishing.store'), [
                'title' => 'Осінній урок',
                'caption' => 'Короткий текст для трьох мереж.',
                'platforms' => ['facebook', 'instagram', 'tiktok'],
                'media_file' => UploadedFile::fake()->image('lesson.jpg', 1080, 1080),
            ]);

        $publication = SocialPublication::firstOrFail();

        $response->assertRedirect(route('admin.social-publishing.edit', $publication));
        $this->assertSame('draft', $publication->status);
        $this->assertSame('image', $publication->media_type);
        $this->assertCount(3, $publication->targets);
        Storage::disk('public')->assertExists($publication->media_path);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_draft_validation_rejects_unknown_platform_and_unsafe_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('admin.social-publishing.create'))
            ->post(route('admin.social-publishing.store'), [
                'title' => 'Unsafe draft',
                'caption' => 'Text',
                'platforms' => ['youtube'],
                'media_file' => UploadedFile::fake()->create('payload.svg', 10, 'image/svg+xml'),
            ])
            ->assertRedirect(route('admin.social-publishing.create'))
            ->assertSessionHasErrors(['platforms.0', 'media_file']);

        $this->assertDatabaseCount('social_publications', 0);
    }

    public function test_at_least_one_target_is_required(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('admin.social-publishing.store'), [
                'title' => 'No target',
                'caption' => 'Text',
                'platforms' => [],
            ])
            ->assertSessionHasErrors('platforms');
    }

    public function test_updating_draft_replaces_targets_without_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $publication = SocialPublication::create([
            'created_by' => $admin->id,
            'title' => 'First title',
            'status' => 'simulated',
        ]);
        $publication->targets()->create(['platform' => 'facebook', 'status' => 'simulated']);

        $this->actingAs($admin)
            ->patch(route('admin.social-publishing.update', $publication), [
                'title' => 'Updated title',
                'caption' => 'Updated caption',
                'platforms' => ['instagram', 'tiktok'],
            ])
            ->assertRedirect(route('admin.social-publishing.edit', $publication));

        $publication->refresh();
        $this->assertSame('draft', $publication->status);
        $this->assertEqualsCanonicalizing(
            ['instagram', 'tiktok'],
            $publication->targets()->pluck('platform')->all(),
        );
        $this->assertSame(2, $publication->targets()->count());
        $this->assertTrue($publication->targets()->where('status', 'pending')->count() === 2);
    }

    public function test_simulation_records_each_target_without_external_requests(): void
    {
        Http::preventStrayRequests();
        config()->set('social-publishing.live_enabled', false);

        $admin = User::factory()->create(['role' => 'admin']);
        $publication = SocialPublication::create([
            'created_by' => $admin->id,
            'title' => 'Dry run',
            'caption' => 'Nothing leaves the server.',
            'status' => 'draft',
        ]);
        $publication->targets()->createMany([
            ['platform' => 'facebook', 'status' => 'pending'],
            ['platform' => 'instagram', 'status' => 'pending'],
            ['platform' => 'tiktok', 'status' => 'pending'],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.social-publishing.publish', $publication))
            ->assertRedirect()
            ->assertSessionHas('success');

        $publication->refresh();
        $this->assertSame('simulated', $publication->status);
        $this->assertNotNull($publication->last_published_at);
        $this->assertSame(3, $publication->targets()->where('status', 'simulated')->count());
        Http::assertNothingSent();
    }

    public function test_live_flag_fails_closed_until_real_adapters_are_configured(): void
    {
        config()->set('social-publishing.live_enabled', true);

        $admin = User::factory()->create(['role' => 'admin']);
        $publication = SocialPublication::create([
            'created_by' => $admin->id,
            'title' => 'Live attempt',
            'status' => 'draft',
        ]);
        $publication->targets()->create(['platform' => 'facebook', 'status' => 'pending']);

        $this->actingAs($admin)
            ->post(route('admin.social-publishing.publish', $publication))
            ->assertStatus(503);

        $this->assertDatabaseHas('social_publications', [
            'id' => $publication->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('social_publication_targets', [
            'social_publication_id' => $publication->id,
            'status' => 'pending',
        ]);
    }

    public function test_deleting_draft_removes_media_and_targets_but_not_site_posts(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::create([
            'title' => 'Website post',
            'content' => 'Must stay intact.',
            'image' => '',
            'is_published' => true,
        ]);
        $path = UploadedFile::fake()->image('draft.jpg')->store('social-publications', 'public');
        $publication = SocialPublication::create([
            'created_by' => $admin->id,
            'title' => 'Delete me',
            'media_path' => $path,
            'media_type' => 'image',
            'status' => 'draft',
        ]);
        $publication->targets()->create(['platform' => 'facebook', 'status' => 'pending']);

        $this->actingAs($admin)
            ->delete(route('admin.social-publishing.delete', $publication))
            ->assertRedirect(route('admin.social-publishing.index'));

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('social_publications', ['id' => $publication->id]);
        $this->assertDatabaseCount('social_publication_targets', 0);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }
}
