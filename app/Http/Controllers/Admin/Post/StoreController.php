<?php

namespace App\Http\Controllers\Admin\Post;

use App\Http\Requests\Post\StoreRequest;

class StoreController extends BaseController
{
    public function __invoke (StoreRequest $request)
    {
        $this->service->store($request->validated());
        return redirect()->route('admin.post.index');
    }
}
