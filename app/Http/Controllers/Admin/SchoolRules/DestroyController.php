<?php

namespace App\Http\Controllers\Admin\SchoolRules;

use App\Http\Controllers\Controller;
use App\Models\SchoolRule;

class DestroyController extends Controller
{
    public function __invoke(SchoolRule $schoolRule)
    {
        $schoolRule->delete();

        return redirect()
            ->route('admin.school-rules.index')
            ->with('success', 'Правило видалено');
    }
}
