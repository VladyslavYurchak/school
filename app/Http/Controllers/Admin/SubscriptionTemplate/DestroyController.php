<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\RedirectResponse;

class DestroyController extends Controller
{
    public function __invoke(SubscriptionTemplate $subscriptionTemplate): RedirectResponse
    {
        $subscriptionTemplate->delete();

        return redirect()
            ->route('admin.subscription-templates.index')
            ->with('success', 'Шаблон абонементу видалено.');
    }
}
