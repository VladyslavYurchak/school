<?php

namespace App\Http\Controllers\Admin\Post;

use App\Models\Post;

class CreateController
{
    public function __invoke()
    {
        return view('admin.post.create');
    }
}
