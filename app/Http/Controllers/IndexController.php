<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\Post\BaseController;
use App\Http\Filters\PostFilter;
use App\Http\Requests\Post\FilterRequest;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Post;
use App\Models\Testing\Test as TestingTest;

class IndexController extends BaseController
{
    public function __invoke(FilterRequest $request)
    {
        $data = $request->validated();
        $filter = app()->make(PostFilter::class, ['queryParams' => array_filter($data)]);

        $posts = Post::filter($filter)
            ->where('is_published', true)
            ->latest()
            ->take(4)
            ->get();

        $events = Event::query()
            ->where('is_published', true)
            ->whereDate('start_date', '>=', now()->toDateString())
            ->orderBy('start_date')
            ->take(3)
            ->get();

        $photos = Photo::query()
            ->latest()
            ->take(8)
            ->get();

        $availableTestingLanguages = TestingTest::query()
            ->publiclyAvailable()
            ->distinct()
            ->pluck('language_code');

        return view('public.index', compact(
            'posts',
            'events',
            'photos',
            'availableTestingLanguages'
        ));
    }
}
