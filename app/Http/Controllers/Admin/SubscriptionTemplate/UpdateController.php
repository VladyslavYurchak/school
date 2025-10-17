<?php

namespace App\Http\Controllers\Admin\SubscriptionTemplate;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class UpdateController extends Controller
{
    public function __invoke(Request $request, SubscriptionTemplate $subscriptionTemplate): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255', // ← замість name
            'type' => 'required|in:individual,group,pair',
            'lessons_per_week' => 'required|integer|min:1|max:7',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);


        $subscriptionTemplate->update($validated);

        return redirect()
            ->route('admin.subscription-templates.index')
            ->with('success', 'Шаблон абонементу оновлено.');
    }
}
