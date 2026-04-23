<?php

namespace App\Http\Controllers\Admin\Testing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Testing\StoreResultRangeRequest;
use App\Http\Requests\Admin\Testing\UpdateResultRangeRequest;
use App\Models\Testing\ResultRange;
use App\Models\Testing\Test;

class ResultRangeController extends Controller
{
    public function index(Test $test)
    {
        $ranges = $test->resultRanges()->paginate(50);

        return view('admin.testing.result-ranges.index', compact('test', 'ranges'));
    }

    public function create(Test $test)
    {
        return view('admin.testing.result-ranges.create', compact('test'));
    }

    public function store(StoreResultRangeRequest $request, Test $test)
    {
        $test->resultRanges()->create($request->validated());

        return redirect()
            ->route('admin.testing.tests.result-ranges.index', $test)
            ->with('success', 'Діапазон результатів створено');
    }

    public function edit(ResultRange $resultRange)
    {
        $test = $resultRange->test;

        return view('admin.testing.result-ranges.edit', compact('resultRange', 'test'));
    }

    public function update(UpdateResultRangeRequest $request, ResultRange $resultRange)
    {
        $resultRange->update($request->validated());

        return redirect()
            ->route('admin.testing.tests.result-ranges.index', $resultRange->test)
            ->with('success', 'Діапазон результатів оновлено');
    }

    public function destroy(ResultRange $resultRange)
    {
        $test = $resultRange->test;
        $resultRange->delete();

        return redirect()
            ->route('admin.testing.tests.result-ranges.index', $test)
            ->with('success', 'Діапазон результатів видалено');
    }
}
