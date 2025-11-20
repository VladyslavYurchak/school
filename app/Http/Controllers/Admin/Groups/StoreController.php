<?php

namespace App\Http\Controllers\Admin\Groups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Groups\StoreRequest;
use Illuminate\Http\Request;
use App\Models\Group;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        Group::create($validated);

        return redirect()
            ->route('admin.groups.index')
            ->with('success', 'Групу успішно створено.');
    }
}
