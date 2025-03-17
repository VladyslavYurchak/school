<?php

namespace App\Http\Controllers\Admin\Post;

use App\Http\Requests\Post\UpdateRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;


class UpdateController extends BaseController
{
    public function __invoke(UpdateRequest $request, Post $post)
    {
        $data = $request->validated();
        $this->service->update($post, $data);
        return redirect()->route('admin.post.show', $post->id);
    }
}
