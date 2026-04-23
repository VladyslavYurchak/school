<?php

namespace App\Http\Controllers\Admin\Testing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Testing\StoreTestRequest;
use App\Http\Requests\Admin\Testing\UpdateTestRequest;
use App\Models\Testing\Test;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::query()
            ->latest()
            ->paginate(20);

        return view('admin.testing.tests.index', compact('tests'));
    }

    public function create()
    {
        return view('admin.testing.tests.create');
    }

    public function store(StoreTestRequest $request)
    {
        Test::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'randomize_questions' => $request->boolean('randomize_questions'),
            'show_result_immediately' => $request->boolean('show_result_immediately'),
        ]);

        return redirect()
            ->route('admin.testing.tests.index')
            ->with('success', 'Тест створено');
    }

    public function show(Test $test)
    {
        return redirect()->route('admin.testing.tests.edit', $test);
    }

    public function edit(Test $test)
    {
        return view('admin.testing.tests.edit', compact('test'));
    }

    public function update(UpdateTestRequest $request, Test $test)
    {
        $test->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'randomize_questions' => $request->boolean('randomize_questions'),
            'show_result_immediately' => $request->boolean('show_result_immediately'),
        ]);

        return redirect()
            ->route('admin.testing.tests.edit', $test)
            ->with('success', 'Тест оновлено');
    }

    public function destroy(Test $test)
    {
        $test->delete();

        return redirect()
            ->route('admin.testing.tests.index')
            ->with('success', 'Тест видалено');
    }
}
