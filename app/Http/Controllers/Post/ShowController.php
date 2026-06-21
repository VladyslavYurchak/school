<?php

namespace App\Http\Controllers\Post;

use App\Http\Controllers\Controller;
use App\Models\Post;

class ShowController extends Controller
{
    public function __invoke(Post $post)
    {
        abort_unless($post->is_published, 404);

        return view('public.posts.show', compact('post'));
    }
}
