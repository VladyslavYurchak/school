<?php

namespace App\Http\Controllers\Admin\Testing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Testing\StoreQuestionRequest;
use App\Http\Requests\Admin\Testing\UpdateQuestionRequest;
use App\Models\Testing\Question;
use App\Models\Testing\Test;

class QuestionController extends Controller
{
    public function index(Test $test)
    {
        $questions = $test->questions()
            ->with('section')
            ->paginate(50);

        return view('admin.testing.questions.index', compact('test', 'questions'));
    }

    public function create(Test $test)
    {
        $sections = $test->sections()->get();
        return view('admin.testing.questions.create', compact('test', 'sections'));
    }

    public function store(StoreQuestionRequest $request, Test $test)
    {
        $question = $test->questions()->create($request->validated());

        $test->refresh();
        $test->recalculateMaxScore();

        return redirect()
            ->route('admin.testing.tests.questions.index', $test)
            ->with('success', 'Питання створено');
    }

    public function edit(Question $question)
    {
        $test = $question->test;
        $sections = $test->sections()->get();

        return view('admin.testing.questions.edit', compact('question', 'test', 'sections'));
    }

    public function update(UpdateQuestionRequest $request, Question $question)
    {
        $question->update($request->validated());

        $test = $question->test;
        $test->refresh();
        $test->recalculateMaxScore();

        return redirect()
            ->route('admin.testing.tests.questions.index', $test)
            ->with('success', 'Питання оновлено');
    }

    public function destroy(Question $question)
    {
        $test = $question->test;
        $question->delete();

        $test->refresh();
        $test->recalculateMaxScore();

        return redirect()
            ->route('admin.testing.tests.questions.index', $test)
            ->with('success', 'Питання видалено');
    }
}
