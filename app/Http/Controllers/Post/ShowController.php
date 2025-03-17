<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Admin\Post\BaseController;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;


class ShowController extends BaseController
{
    public function __invoke(Post $post)
    {
        return view('posts.show', compact('post'));
    }
}
