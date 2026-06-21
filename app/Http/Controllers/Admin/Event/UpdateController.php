<?php

namespace App\Http\Controllers\Admin\Event;

use App\Http\Requests\Event\UpdateRequest;
use App\Models\Event;

class UpdateController extends BaseController
{
    public function __invoke(UpdateRequest $request, Event $event)
    {
        $this->service->update($event, $request->validated());

        return redirect()->route('admin.event.show', $event);
    }
}
