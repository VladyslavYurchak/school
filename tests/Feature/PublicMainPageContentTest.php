<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMainPageContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_page_shows_only_public_latest_content(): void
    {
        Post::create([
            'title' => 'Published post',
            'content' => 'Useful school news',
            'image' => 'posts/published.jpg',
            'is_published' => true,
        ]);

        Post::create([
            'title' => 'Draft post',
            'content' => 'Hidden draft',
            'image' => 'posts/draft.jpg',
            'is_published' => false,
        ]);

        $futureEventDate = now()->addWeek();

        Event::create([
            'title' => 'Future event',
            'image' => null,
            'start_date' => $futureEventDate->toDateString(),
            'is_published' => true,
        ]);

        Event::create([
            'title' => 'Past event',
            'image' => null,
            'start_date' => now()->subWeek()->toDateString(),
            'is_published' => true,
        ]);

        Event::create([
            'title' => 'Draft event',
            'image' => null,
            'start_date' => now()->addWeeks(2)->toDateString(),
            'is_published' => false,
        ]);

        Photo::create(['path' => 'photos/school.jpg']);

        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('Published post')
            ->assertSee('Useful school news')
            ->assertSee('Future event')
            ->assertSee('photos/school.jpg')
            ->assertDontSee($futureEventDate->format('d.m.Y'))
            ->assertDontSee('Draft post')
            ->assertDontSee('Past event')
            ->assertDontSee('Draft event');
    }

    public function test_main_page_has_friendly_empty_states(): void
    {
        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('Фото скоро з', false)
            ->assertSee('Подій поки немає')
            ->assertSee('Публікацій поки немає');
    }

    public function test_unpublished_post_is_not_publicly_accessible(): void
    {
        $post = Post::create([
            'title' => 'Draft post',
            'content' => 'Hidden draft',
            'image' => 'posts/draft.jpg',
            'is_published' => false,
        ]);

        $this
            ->get(route('posts.show', $post))
            ->assertNotFound();
    }

    public function test_public_post_images_support_external_urls(): void
    {
        $imageUrl = 'https://s0.tchkcdn.com/g-49bK-ihki_h_sf4gLEKMHQ/17/261234/660x480/f/0/05d_2a99744_dyka_pryroda_bilka_ta_tulpan.jpg';

        $post = Post::create([
            'title' => 'External image post',
            'content' => 'Post with external image.',
            'image' => $imageUrl,
            'is_published' => true,
        ]);

        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('src="' . $imageUrl . '"', false)
            ->assertDontSee('storage/https://', false);

        $this
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('src="' . $imageUrl . '"', false)
            ->assertDontSee('storage/https://', false);
    }

    public function test_public_post_images_support_storage_paths(): void
    {
        Post::create([
            'title' => 'Storage image post',
            'content' => 'Post with storage image.',
            'image' => 'posts/storage-image.jpg',
            'is_published' => true,
        ]);

        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('/storage/posts/storage-image.jpg', false);
    }

    public function test_public_post_page_does_not_crop_the_full_image(): void
    {
        $post = Post::create([
            'title' => 'Square image post',
            'content' => 'The complete image should remain visible.',
            'image' => 'posts/square.webp',
            'is_published' => true,
        ]);

        $this
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('class="post-show-image"', false);

        $scss = file_get_contents(resource_path('sass/public/home.scss'));

        $this->assertStringContainsString('width: min(100%, 760px);', $scss);
        $this->assertStringNotContainsString('max-height: 460px;', $scss);
    }

    public function test_public_event_images_support_external_urls(): void
    {
        $imageUrl = 'https://example.com/events/open-day.jpg';

        Event::create([
            'title' => 'External image event',
            'image' => $imageUrl,
            'start_date' => now()->addWeek()->toDateString(),
            'is_published' => true,
        ]);

        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('src="' . $imageUrl . '"', false)
            ->assertSee('class="event-image-wrap"', false)
            ->assertDontSee('storage/https://', false);
    }

    public function test_public_header_social_links_are_real_contact_links(): void
    {
        $this
            ->get(route('index'))
            ->assertOk()
            ->assertSee('https://www.instagram.com/korporatsiia.mov/', false)
            ->assertSee('https://t.me/DashaYurchak', false)
            ->assertSee('tel:+380662992218', false)
            ->assertSee('https://www.facebook.com/people/%D0%9A%D0%BE%D1%80%D0%BF%D0%BE%D1%80%D0%B0%D1%86%D1%96%D1%8F-%D0%BC%D0%BE%D0%B2/61558067528774/', false)
            ->assertSee('https://www.tiktok.com/@korporatsiia.mov', false)
            ->assertDontSee('<div class="social-icons"><a href="#">', false);
    }
}
