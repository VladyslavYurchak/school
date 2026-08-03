<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class SeoLandingPageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = config("seo_pages.pages.{$slug}");

        abort_unless(is_array($page), 404);

        $relatedPages = collect($page['related'] ?? [])
            ->map(function (string $relatedSlug): ?array {
                $relatedPage = config("seo_pages.pages.{$relatedSlug}");

                if (! is_array($relatedPage)) {
                    return null;
                }

                return [
                    'slug' => $relatedSlug,
                    'title' => $relatedPage['link_title'] ?? $relatedPage['heading'],
                    'summary' => $relatedPage['link_summary'] ?? $relatedPage['lead'],
                ];
            })
            ->filter()
            ->values();

        return view('public.seo.landing', compact('page', 'slug', 'relatedPages'));
    }
}
