<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect([
            $this->entry(route('index'), null),
            $this->entry(route('teachers.index'), null),
            $this->entry(route('contact.index'), null),
            $this->entry(route('rules.index'), null),
            $this->entry(route('privacy-policy'), null),
        ]);

        $urls = $urls
            ->merge($this->seoLandingEntries())
            ->merge($this->postEntries())
            ->merge($this->courseEntries());

        return response()
            ->view('public.seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function seoLandingEntries(): Collection
    {
        $lastModified = CarbonImmutable::parse(config('seo_pages.last_modified'));

        return collect(config('seo_pages.pages', []))
            ->keys()
            ->map(fn (string $slug) => $this->entry(
                route('seo.show', ['slug' => $slug]),
                $lastModified
            ));
    }

    private function postEntries(): Collection
    {
        return Post::query()
            ->where('is_published', true)
            ->orderBy('id')
            ->get(['id', 'updated_at'])
            ->map(fn (Post $post) => $this->entry(
                route('posts.show', $post),
                $post->updated_at
            ));
    }

    private function courseEntries(): Collection
    {
        $courses = Course::query()
            ->where('is_published', true)
            ->orderBy('id')
            ->get(['id', 'updated_at']);

        if ($courses->isEmpty()) {
            return collect();
        }

        return collect([$this->entry(
            route('courses.index'),
            $courses->max('updated_at')
        )])
            ->merge($courses->map(fn (Course $course) => $this->entry(
                route('courses.show', $course),
                $course->updated_at
            )));
    }

    private function entry(string $location, $lastModified): array
    {
        return [
            'location' => $location,
            'last_modified' => $lastModified?->toAtomString(),
        ];
    }
}
