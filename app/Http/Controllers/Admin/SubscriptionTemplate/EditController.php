<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;

class EditController extends Controller
{
    public function __invoke(SubscriptionTemplate $subscriptionTemplate)
    {
        return view('admin.subscription_templates.edit', compact('subscriptionTemplate'));
    }
}

