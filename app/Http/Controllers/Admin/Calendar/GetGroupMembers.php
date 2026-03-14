<?php

namespace App\Http\Controllers\Admin\Calendar;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;

class GetGroupMembers extends Controller
{
    public function __invoke($groupId)
    {
        $group = Group::with('students')->find($groupId);

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
