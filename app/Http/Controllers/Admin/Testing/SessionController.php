<?php

namespace App\Http\Controllers\Admin\Testing;

use App\Http\Controllers\Controller;
use App\Models\Testing\Session;

class SessionController extends Controller
{
    public function index()
    {
        $sessions = Session::query()
            ->with(['lead', 'attempts.test'])
            ->latest()
            ->paginate(20);

        return view('admin.testing.sessions.index', compact('sessions'));
    }

    public function show(Session $session)
    {
        $session->load([
            'lead',
            'attempts.test',
            'attempts.answers.question',
            'resultRange',
        ]);

        return view('admin.testing.sessions.show', compact('session'));
    }
}
