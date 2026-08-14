<?php

namespace Tests\Feature;

use App\Models\Post;
use Database\Seeders\LocalSeoArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalSeoArticleSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_publishes_the_local_article_without_creating_duplicates(): void
    {
        $this->seed(LocalSeoArticleSeeder::class);
        $this->seed(LocalSeoArticleSeeder::class);

        $post = Post::query()
            ->where('title', 'Як обрати школу англійської у Броварах: 7 практичних критеріїв')
            ->sole();

        $this->assertTrue($post->is_published);
        $this->assertSame('posts/shkola-angliiskoi-brovary.png', $post->image);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<h2>1. Почніть із мети навчання</h2>', false)
            ->assertSee('data-bs-target="#trialLessonRequestModal"', false)
            ->assertSee(route('seo.show', ['slug' => 'shkola-angliiskoi-brovary']), false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('posts.show', $post), false);
    }
}
