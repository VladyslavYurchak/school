<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Controllers\Admin\Event\BaseController;
use App\Http\Requests\Event\StoreRequest;
use App\Models\Event;
use Illuminate\Routing\Controller;

class StoreController extends BaseController
{
    public function __invoke(StoreRequest $request) // Тільки один аргумент
    {
        $this->service->store($request->validated());
        return redirect()->route('admin.event.index');
    }
}
