<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\RedirectResponse;

class DestroyController extends Controller
{
    public function __invoke(SubscriptionTemplate $subscriptionTemplate): RedirectResponse
    {
        $subscriptionTemplate->update(['is_active' => false]);

        return redirect()
            ->route('admin.subscription-templates.index')
            ->with('success', 'Шаблон абонементу перенесено в архів. Прив’язки учнів та історію оплат збережено.');
    }
}
