<?php

namespace App\Http\Controllers\Testing;

use App\Http\Controllers\Controller;
use App\Models\Testing\Lead;
use App\Models\Testing\Session;
use App\Models\Testing\Test;
use App\Services\Testing\ScoreCalculatorService;
use App\Services\Testing\TestSubmissionService;
use Illuminate\Http\Request;

class PublicTestingController extends Controller
{
    public function start(string $language)
    {
        $tests = Test::where('language_code', $language)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($tests->isEmpty()) {
            return back()->with('error', 'Тести для цієї мови ще не додані');
        }

        $session = Session::create([
            'language_code' => $language,
            'status' => 'in_progress',
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

        $attempts = $session->attempts->values();
        $currentAttempt = $attempts->first();
        $timeLimitMinutes = $currentAttempt->test->time_limit_minutes;
        $startedAt = optional($session->started_at)->timestamp;

        if (! $currentAttempt) {
            return redirect()->route('testing.session.result', $session);
        }

        $sections = $currentAttempt->test->sections->values();
        $totalSteps = $sections->count();
        $step = max(1, min((int) $request->integer('step', 1), $totalSteps));

        $currentSection = $sections->get($step - 1);

        if (! $currentSection) {
            return redirect()->route('testing.session.result', $session);
        }

        return view('testing.session', [
            'session' => $session,
            'currentAttempt' => $currentAttempt,
            'currentSection' => $currentSection,
            'step' => $step,
            'totalSteps' => $totalSteps,
            'timeLimitMinutes' => $timeLimitMinutes,
            'startedAt' => $startedAt
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

        $attempts = $session->attempts->values();
        $currentAttempt = $attempts->first();

        if (! $currentAttempt) {
            return redirect()->route('testing.session.result', $session);
        }

        $sections = $currentAttempt->test->sections->values();
        $totalSteps = $sections->count();
        $step = max(1, min((int) $request->integer('step', 1), $totalSteps));

        $currentSection = $sections->get($step - 1);

        if (! $currentSection) {
            return redirect()->route('testing.session.result', $session);
        }

        $answers = $request->input('answers', []);


        $sectionQuestionIds = $currentSection->questions->pluck('id')->all();

        $sectionAnswers = collect($answers)
            ->only($sectionQuestionIds)
            ->toArray();

        $submissionService->submitSingleAttemptAnswers($currentAttempt, $sectionAnswers);

        $nextStep = $step + 1;

        if ($nextStep <= $totalSteps) {
            return redirect()->route('testing.session.show', [
                'session' => $session,
                'step' => $nextStep,
            ]);
        }

        $currentAttempt->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        $scoreCalculator->recalculateAttempt($currentAttempt->fresh([
            'test.sections.questions.options',
            'answers.question',
        ]));

        $session->update([
            'status' => 'completed',
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
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telegram' => ['nullable', 'string', 'max:255'],
            'age' => ['nullable', 'integer', 'min:1', 'max:120'],
            'preferred_study_format' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'contact_consent' => ['nullable', 'boolean'],
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
}
