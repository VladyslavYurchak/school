<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\Post\BaseController;
use App\Http\Filters\PostFilter;
use App\Http\Requests\Post\FilterRequest;
use App\Models\Event;
use App\Models\Photo;
use App\Models\Post;

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

        return view('public.index', compact('posts', 'events', 'photos'));
    }
}
