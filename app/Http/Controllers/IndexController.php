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

        // Отримуємо пости з фільтрацією
        $posts = Post::filter($filter)->paginate(10);

        // Отримуємо події
        $events = Event::query()->paginate(10);

        // Отримуємо фотографії
        $photos = Photo::all(); // або можна використати інший запит, якщо потрібно обмежити кількість фото

        // Повертаємо дані у вигляд
        return view('index.index', compact('posts', 'events', 'photos'));
    }
}
