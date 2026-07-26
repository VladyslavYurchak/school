<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\Testing\Lead;
use App\Models\Testing\Session;
use App\Models\Testing\Test;
use App\Services\Testing\ScoreCalculatorService;
use App\Services\Testing\TestSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicTestingController extends Controller
{
    public function start(string $language)
    {
        $tests = Test::where('language_code', $language)
            ->publiclyAvailable()
            ->orderBy('sort_order')
            ->get();

        if ($tests->isEmpty()) {
            return back()->with('error', 'Тести для цієї мови ще не додані');
        }

        $session = Session::create([
            'language_code' => $language,
            'status' => 'in_progress',
            'current_step' => 1,
            'started_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        foreach ($tests as $test) {
            $session->attempts()->create([
                'test_id' => $test->id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        return redirect()->route('testing.session.show', ['session' => $session, 'step' => 1]);
    }

    public function show(Session $session, Request $request)
    {
        $session->load([
            'attempts.test.sections.questions.options',
        ]);

        $steps = $this->buildTestingSteps($session);

        if ($steps->isEmpty()) {
            abort(404);
        }

        if ($session->status === 'completed') {
            return redirect()->route('testing.session.result', $session);
        }

        $totalSteps = $steps->count();
        $step = max(1, min($session->current_step, $totalSteps));

        if ($request->integer('step', $step) !== $step) {
            return redirect()->route('testing.session.show', [
                'session' => $session,
                'step' => $step,
            ]);
        }

        $currentStep = $steps->get($step - 1);
        $currentAttempt = $currentStep['attempt'];
        $currentSection = $currentStep['section'];
        $timeLimitMinutes = $currentAttempt->test->time_limit_minutes;
        $startedAt = optional($session->started_at)->timestamp;
        $displayQuestions = $this->questionsForDisplay($session, $currentAttempt, $currentSection);

        return view('testing.session', [
            'session' => $session,
            'currentAttempt' => $currentAttempt,
            'currentSection' => $currentSection,
            'step' => $step,
            'totalSteps' => $totalSteps,
            'timeLimitMinutes' => $timeLimitMinutes,
            'startedAt' => $startedAt,
            'displayQuestions' => $displayQuestions,
        ]);
    }

    public function submit(
        Session $session,
        Request $request,
        TestSubmissionService $submissionService,
        ScoreCalculatorService $scoreCalculator
    ) {
        $session->load([
            'attempts.test.sections.questions.options',
            'attempts.answers.question',
        ]);

        $steps = $this->buildTestingSteps($session);

        if ($steps->isEmpty()) {
            abort(404);
        }

        if ($session->status === 'completed') {
            return redirect()->route('testing.session.result', $session);
        }

        $totalSteps = $steps->count();
        $step = max(1, min($session->current_step, $totalSteps));

        if ($request->integer('step') !== $step) {
            return redirect()->route('testing.session.show', [
                'session' => $session,
                'step' => $step,
            ]);
        }

        $currentStep = $steps->get($step - 1);
        $currentAttempt = $currentStep['attempt'];
        $currentSection = $currentStep['section'];

        $activeQuestions = $currentSection->questions->where('is_active', true);
        $timeLimitMinutes = $currentAttempt->test->time_limit_minutes;
        $hasExpired = $timeLimitMinutes
            && $session->started_at
            && $session->started_at->copy()->addMinutes($timeLimitMinutes)->isPast();

        if (! $hasExpired) {
            $request->validate($this->answerRules($activeQuestions));
        }

        $sectionAnswers = collect($request->input('answers', []))
            ->only($activeQuestions->pluck('id'))
            ->toArray();

        $submissionService->submitSingleAttemptAnswers($currentAttempt, $sectionAnswers);

        $nextStep = $step + 1;

        if ($nextStep <= $totalSteps) {
            $session->update(['current_step' => $nextStep]);

            return redirect()->route('testing.session.show', [
                'session' => $session,
                'step' => $nextStep,
            ]);
        }

        foreach ($session->attempts as $attempt) {
            $attempt->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            $scoreCalculator->recalculateAttempt($attempt->fresh([
                'test.sections.questions.options',
                'answers.question',
            ]));
        }

        $session->update([
            'status' => 'completed',
            'current_step' => $totalSteps,
            'finished_at' => now(),
        ]);

        $scoreCalculator->recalculateSession($session->fresh([
            'attempts.test',
            'attempts.answers.question',
        ]));


        return redirect()->route('testing.session.result', $session);
    }

    public function result(Session $session)
    {
        if ($session->status !== 'completed') {
            return redirect()->route('testing.session.show', [
                'session' => $session,
                'step' => $session->current_step,
            ]);
        }

        $session->load([
            'attempts.test.sections.questions.options',
            'attempts.answers.question',
            'attempts.answers.selectedOption',
            'resultRange',
            'lead',
        ]);

        return view('testing.result', compact('session'));
    }

    public function storeLead(Session $session, Request $request)
    {
        abort_unless($session->status === 'completed', 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255', 'required_without_all:email,telegram'],
            'email' => ['nullable', 'email', 'max:255', 'required_without_all:phone,telegram'],
            'telegram' => ['nullable', 'string', 'max:255', 'required_without_all:phone,email'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'preferred_study_format' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'contact_consent' => ['accepted'],
        ]);

        $data['contact_consent'] = $request->boolean('contact_consent');

        Lead::updateOrCreate(
            ['session_id' => $session->id],
            $data
        );

        return redirect()
            ->route('testing.session.result', $session)
            ->with('success', 'Дякуємо! Ми зв’яжемося з вами найближчим часом.');
    }

    protected function buildTestingSteps(Session $session)
    {
        return $session->attempts
            ->sortBy(fn ($attempt) => [$attempt->test->sort_order ?? 0, $attempt->id])
            ->values()
            ->flatMap(function ($attempt) {
                return $attempt->test->sections
                    ->where('is_active', true)
                    ->filter(fn ($section) => $section->questions->where('is_active', true)->isNotEmpty())
                    ->values()
                    ->map(fn ($section) => [
                        'attempt' => $attempt,
                        'section' => $section,
                    ]);
            })
            ->values();
    }

    protected function answerRules($questions): array
    {
        $rules = [
            'answers' => ['nullable', 'array'],
        ];

        foreach ($questions as $question) {
            $key = "answers.{$question->id}";
            $presence = $question->is_required ? 'required' : 'nullable';

            if (in_array($question->type, ['single_choice', 'true_false'], true)) {
                $rules[$key] = [
                    $presence,
                    'integer',
                    Rule::exists('testing_options', 'id')
                        ->where('question_id', $question->id),
                ];
            } elseif ($question->type === 'multiple_choice') {
                $rules[$key] = [$presence, 'array', 'min:1'];
                $rules["{$key}.*"] = [
                    'integer',
                    Rule::exists('testing_options', 'id')
                        ->where('question_id', $question->id),
                ];
            } else {
                $rules[$key] = [$presence, 'string', 'max:10000'];
            }
        }

        return $rules;
    }

    protected function questionsForDisplay(Session $session, $attempt, $section)
    {
        $questions = $section->questions
            ->where('is_active', true)
            ->values();

        if (! $attempt->test->randomize_questions) {
            return $questions;
        }

        return $questions
            ->sortBy(fn ($question) => hash(
                'sha256',
                "{$session->public_token}:{$section->id}:{$question->id}"
            ))
            ->values();
    }
}
