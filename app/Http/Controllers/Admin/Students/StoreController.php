<?php

namespace App\Http\Controllers\Admin\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Students\StoreRequest;
use App\Models\Student;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        Student::create($request->validated());
        return redirect()->route('admin.students.index')->with('success', 'Учня успішно додано');
    }
}
