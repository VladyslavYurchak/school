<?php

namespace App\Http\Controllers;

use App\Models\TrialLessonRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrialLessonRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'preferred_contact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        TrialLessonRequest::create($data);

        return back()
            ->with('trial_request_success', 'Дякуємо! Ми отримали заявку і скоро зв’яжемося з вами.')
            ->with('analytics_event', [
                'name' => 'generate_lead',
                'parameters' => [
                    'method' => 'trial_lesson_form',
                ],
            ]);
    }
}
