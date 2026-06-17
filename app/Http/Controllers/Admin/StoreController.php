<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\TrialLessonRequest;


class StoreController extends Controller
{

    public function __invoke()
    {
        $user = auth()->user();

        return view('admin.index', [
            'newTrialLessonRequests' => $user?->isAdmin()
                ? TrialLessonRequest::query()->new()->latest()->limit(10)->get()
                : collect(),
            'newTrialLessonRequestsCount' => $user?->isAdmin()
                ? TrialLessonRequest::query()->new()->count()
                : 0,
        ]);
    }
}
