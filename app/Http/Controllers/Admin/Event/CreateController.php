<?php

namespace App\Http\Controllers\Admin\Event;

use App\Models\Post;

class CreateController
{
    public function __invoke()
    {
        return view('admin.event.create');
    }
}
