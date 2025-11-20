<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionTemplate\StoreRequest;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request): RedirectResponse
    {
        SubscriptionTemplate::create($request->validated());

        return redirect()
            ->route('admin.subscription-templates.index')
            ->with('success', 'Шаблон абонементу успішно створено.');
    }
}
