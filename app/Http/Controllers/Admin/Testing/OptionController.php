<?php

namespace App\Http\Controllers\Admin\Testing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Testing\StoreOptionRequest;
use App\Http\Requests\Admin\Testing\UpdateOptionRequest;
use App\Models\Testing\Option;
use App\Models\Testing\Question;

class OptionController extends Controller
{
    public function index(Question $question)
    {
        $options = $question->options()->paginate(50);

        return view('admin.testing.options.index', compact('question', 'options'));
    }

    public function create(Question $question)
    {
        return view('admin.testing.options.create', compact('question'));
    }

    public function store(StoreOptionRequest $request, Question $question)
    {
        $question->options()->create($request->validated());

        $test = $question->test;
        $test->refresh();
        $test->recalculateMaxScore();

        return redirect()
            ->route('admin.testing.questions.options.index', $question)
            ->with('success', 'Варіант відповіді створено');
    }

    public function edit(Option $option)
    {
        $question = $option->question;

        return view('admin.testing.options.edit', compact('option', 'question'));
    }

    public function update(UpdateOptionRequest $request, Option $option)
    {
        $option->update($request->validated());

        $question = $option->question;
        $test = $question->test;
        $test->refresh();
        $test->recalculateMaxScore();

        return redirect()
            ->route('admin.testing.questions.options.index', $question)
            ->with('success', 'Варіант відповіді оновлено');
    }

    public function destroy(Option $option)
    {
        $question = $option->question;
        $option->delete();

        $test = $question->test;
        $test->refresh();
        $test->recalculateMaxScore();

        return redirect()
            ->route('admin.testing.questions.options.index', $question)
            ->with('success', 'Варіант відповіді видалено');
    }
}
