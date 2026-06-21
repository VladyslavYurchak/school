<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminEventUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_event_index_uses_admin_page_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Event::create([
            'title' => 'Open day',
            'image' => null,
            'start_date' => now()->addWeek()->toDateString(),
            'is_published' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.event.index'))
            ->assertOk()
            ->assertSee('class="admin-page"', false)
            ->assertSee('Open day')
            ->assertSee('Опубліковано');

        $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));
    }

    public function test_admin_event_form_can_create_draft(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->post(route('admin.event.store'), [
                'title' => 'Draft event',
                'start_date' => now()->addMonth()->toDateString(),
                'image' => '',
                'is_published' => 0,
            ])
            ->assertRedirect(route('admin.event.index'));

        $this->assertDatabaseHas('events', [
            'title' => 'Draft event',
            'is_published' => false,
        ]);
    }

    public function test_admin_event_form_can_upload_image_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->post(route('admin.event.store'), [
                'title' => 'Event with upload',
                'start_date' => now()->addMonth()->toDateString(),
                'image' => 'https://example.com/old.jpg',
                'image_file' => UploadedFile::fake()->image('event.jpg'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.event.index'));

        $event = Event::where('title', 'Event with upload')->firstOrFail();

        $this->assertStringStartsWith('events/', $event->image);
        Storage::disk('public')->assertExists($event->image);
    }

    public function test_admin_event_form_rejects_title_longer_than_database_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this
            ->actingAs($admin)
            ->from(route('admin.event.create'))
            ->post(route('admin.event.store'), [
                'title' => str_repeat('A', 256),
                'start_date' => now()->addMonth()->toDateString(),
                'image' => '',
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.event.create'))
            ->assertSessionHasErrors('title');
    }

    public function test_admin_event_edit_form_updates_publish_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create([
            'title' => 'Old event',
            'image' => null,
            'start_date' => now()->addWeek()->toDateString(),
            'is_published' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.event.edit', $event))
            ->assertOk()
            ->assertSee('name="is_published"', false)
            ->assertSee('name="image_file"', false);

        $this->assertSame(1, substr_count($response->getContent(), '<main class="app-main">'));

        $this
            ->actingAs($admin)
            ->patch(route('admin.event.update', $event), [
                'title' => 'Updated event',
                'start_date' => now()->addWeeks(2)->toDateString(),
                'image' => '',
                'is_published' => 0,
            ])
            ->assertRedirect(route('admin.event.show', $event));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated event',
            'is_published' => false,
        ]);
    }

    public function test_admin_event_update_can_replace_image_with_uploaded_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create([
            'title' => 'Old event',
            'image' => 'https://example.com/old.jpg',
            'start_date' => now()->addWeek()->toDateString(),
            'is_published' => true,
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.event.update', $event), [
                'title' => 'Updated event',
                'start_date' => now()->addWeeks(2)->toDateString(),
                'image' => $event->image,
                'image_file' => UploadedFile::fake()->image('updated-event.jpg'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.event.show', $event));

        $event->refresh();

        $this->assertStringStartsWith('events/', $event->image);
        Storage::disk('public')->assertExists($event->image);
    }

    public function test_admin_event_update_rejects_title_longer_than_database_column(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = Event::create([
            'title' => 'Old event',
            'image' => null,
            'start_date' => now()->addWeek()->toDateString(),
            'is_published' => true,
        ]);

        $this
            ->actingAs($admin)
            ->from(route('admin.event.edit', $event))
            ->patch(route('admin.event.update', $event), [
                'title' => str_repeat('A', 256),
                'start_date' => now()->addWeeks(2)->toDateString(),
                'image' => '',
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.event.edit', $event))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Old event',
        ]);
    }
}
