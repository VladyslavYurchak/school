<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SubscriptionTemplate\UpdateRequest;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, SubscriptionTemplate $subscriptionTemplate): RedirectResponse
    {

        $subscriptionTemplate->update($request->validated());

        return redirect()
            ->route('admin.subscription-templates.index')
            ->with('success', 'Шаблон абонементу оновлено.');
    }
}
