<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\RedirectResponse;

class EditController extends Controller
{
    public function __invoke(SubscriptionTemplate $subscriptionTemplate): RedirectResponse
    {
        return redirect()->route('admin.subscription-templates.index');
    }
}
