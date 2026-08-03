<?php

namespace Tests\Feature;

use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_exposes_local_seo_metadata_and_structured_data(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('<title>Школа англійської мови у Броварах | Корпорація Мов</title>', false)
            ->assertSee('name="description"', false)
            ->assertSee('name="robots" content="index, follow, max-image-preview:large"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('application/ld+json', false)
            ->assertSee('Героїв Крут', false);
    }

    public function test_private_pages_are_marked_noindex(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);
    }

    public function test_sitemap_contains_only_published_posts(): void
    {
        $published = Post::factory()->create(['is_published' => true]);
        $draft = Post::factory()->create(['is_published' => false]);

        $response = $this->get('/sitemap.xml');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('index'), false)
            ->assertSee(route('seo.show', ['slug' => 'shkola-angliiskoi-brovary']), false)
            ->assertSee(route('seo.show', ['slug' => 'angliiska-dlia-shkoliariv']), false)
            ->assertSee(route('posts.show', $published), false)
            ->assertDontSee(route('posts.show', $draft), false)
            ->assertDontSee(route('courses.index'), false);
    }

    public function test_sitemap_exposes_last_modified_date_for_static_seo_pages(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(
                '<lastmod>'.CarbonImmutable::parse(config('seo_pages.last_modified'))->toAtomString().'</lastmod>',
                false
            );
    }

    public function test_seo_landing_metadata_is_unique_and_related_links_are_valid(): void
    {
        $pages = config('seo_pages.pages');

        $this->assertSameSize($pages, array_unique(array_column($pages, 'title')));
        $this->assertSameSize($pages, array_unique(array_column($pages, 'description')));
        $this->assertSameSize($pages, array_unique(array_column($pages, 'heading')));

        foreach ($pages as $slug => $page) {
            $this->assertNotEmpty($page['faq'], "SEO page {$slug} must have an FAQ.");
            $this->assertNotEmpty($page['related'], "SEO page {$slug} must have related links.");

            foreach ($page['related'] as $relatedSlug) {
                $this->assertArrayHasKey(
                    $relatedSlug,
                    $pages,
                    "SEO page {$slug} links to missing page {$relatedSlug}."
                );
                $this->assertNotSame($slug, $relatedSlug);
            }
        }
    }

    public function test_every_configured_seo_landing_page_is_public_and_has_unique_metadata(): void
    {
        foreach (config('seo_pages.pages') as $slug => $page) {
            $response = $this->get(route('seo.show', ['slug' => $slug]));

            $response
                ->assertOk()
                ->assertSee('<title>'.$page['title'].'</title>', false)
                ->assertSee('name="description" content="'.$page['description'].'"', false)
                ->assertSee('rel="canonical" href="'.route('seo.show', ['slug' => $slug]).'"', false)
                ->assertSee('<h1>'.$page['heading'].'</h1>', false)
                ->assertSee('"@type":"Service"', false)
                ->assertSee('"@type":"FAQPage"', false)
                ->assertSee('name="robots" content="index, follow, max-image-preview:large"', false);
        }
    }

    public function test_unknown_seo_slug_remains_a_404(): void
    {
        $this->get('/not-a-real-service-page')->assertNotFound();
    }

    public function test_contact_page_has_local_metadata_and_business_schema(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('<title>Контакти школи англійської у Броварах | Корпорація Мов</title>', false)
            ->assertSee('вул. Героїв Крут, 12, ЖК Scandia, 1 поверх', false)
            ->assertSee('https://maps.app.goo.gl/VE7SfEG7ELQosbbX9', false)
            ->assertSee('Понеділок – Субота', false)
            ->assertSee('09:00 – 19:00', false)
            ->assertSee('"openingHoursSpecification"', false)
            ->assertSee('"price":3200', false)
            ->assertSee('"price":0', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_post_has_unique_metadata_and_article_schema(): void
    {
        $post = Post::factory()->create([
            'title' => 'Як обрати викладача англійської',
            'content' => 'Практичний матеріал для учнів та батьків.',
            'is_published' => true,
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<title>Як обрати викладача англійської | Корпорація Мов</title>', false)
            ->assertSee('property="og:type" content="article"', false)
            ->assertSee('"@type":"Article"', false)
            ->assertSee('"mainEntityOfPage":"'.route('posts.show', $post).'"', false);
    }

    public function test_empty_course_catalog_is_not_indexed(): void
    {
        $this->get('/courses')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false);
    }

    public function test_robots_file_points_to_the_sitemap(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));

        $this->assertIsString($robots);
        $this->assertStringContainsString(
            'Sitemap: https://korporatsiia-mov.com/sitemap.xml',
            $robots
        );
    }
}
