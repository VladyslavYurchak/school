<?php

namespace App\Http\Controllers\Admin\TrialLessonRequests;

use App\Http\Controllers\Controller;
use App\Models\TrialLessonRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MarkContactedController extends Controller
{
    public function __invoke(Request $request, TrialLessonRequest $trialLessonRequest): RedirectResponse
    {
        $trialLessonRequest->markContacted($request->user());

        return redirect()
            ->route('admin.index')
            ->with('success', 'Заявку позначено як оброблену.');
    }
}
