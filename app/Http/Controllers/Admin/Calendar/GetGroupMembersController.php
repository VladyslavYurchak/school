<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\Calendar\CalendarAccessService;

class GetGroupMembersController extends Controller
{
    public function __invoke($groupId, CalendarAccessService $access)
    {
        $group = $access
            ->scopeGroupForUser(Group::with('students')->whereKey($groupId), auth()->user())
            ->first();

        if (!$group) {
            return response()->json(['message' => 'Група не знайдена'], 404);
        }


        $members = $group->students->map(function ($student) {

            return [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
            ];
        });

        return response()->json(['members' => $members]);
    }
}
