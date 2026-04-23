<?php

namespace App\Http\Controllers\Admin\Testing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Testing\StoreSectionRequest;
use App\Http\Requests\Admin\Testing\UpdateSectionRequest;
use App\Models\Testing\Section;
use App\Models\Testing\Test;

class SectionController extends Controller
{
    public function index(Test $test)
    {
        $sections = $test->sections()->paginate(50);

        return view('admin.testing.sections.index', compact('test', 'sections'));
    }

    public function create(Test $test)
    {
        return view('admin.testing.sections.create', compact('test'));
    }

    public function store(StoreSectionRequest $request, Test $test)
    {
        $data = $request->validated();

        $typeTitles = [
            'grammar' => 'Grammar',
            'reading' => 'Reading',
            'listening' => 'Listening',
        ];

        if (empty($data['title'])) {
            $data['title'] = $typeTitles[$data['type']] ?? ucfirst($data['type']);
        }

        $test->sections()->create([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.testing.tests.sections.index', $test)
            ->with('success', 'Секцію створено');
    }

    public function edit(Section $section)
    {
        $test = $section->test;

        return view('admin.testing.sections.edit', compact('section', 'test'));
    }

    public function update(UpdateSectionRequest $request, Section $section)
    {
        $data = $request->validated();

        $typeTitles = [
            'grammar' => 'Grammar',
            'reading' => 'Reading',
            'listening' => 'Listening',
        ];

        if (empty($data['title'])) {
            $data['title'] = $typeTitles[$data['type']] ?? ucfirst($data['type']);
        }

        $section->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.testing.tests.sections.index', $section->test)
            ->with('success', 'Секцію оновлено');
    }

    public function destroy(Section $section)
    {
        $test = $section->test;
        $section->delete();

        return redirect()
            ->route('admin.testing.tests.sections.index', $test)
            ->with('success', 'Секцію видалено');
    }
}
