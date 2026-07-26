@extends('public.layouts.main')

@section('content')
    <div class="container testing-page">
        <div class="testing-shell">
            <header class="testing-heading">
                <div class="testing-kicker">Безкоштовне визначення рівня</div>
                <h1>Тестування з іноземної мови</h1>
                <div class="testing-step">Частина {{ $step }} з {{ $totalSteps }}</div>
            </header>

            <div class="testing-meta">
                <div class="testing-progress"
                     role="progressbar"
                     aria-label="Прогрес тестування"
                     aria-valuenow="{{ $step }}"
                     aria-valuemin="1"
                     aria-valuemax="{{ $totalSteps }}">
                    <div class="testing-progress-bar" style="width: {{ ($step / $totalSteps) * 100 }}%"></div>
                </div>

                @if(!empty($timeLimitMinutes))
                    <div class="testing-timer">
                        <span>Залишилось часу</span>
                        <strong id="test-timer">--:--</strong>
                    </div>
                @endif
            </div>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    Будь ласка, дайте відповідь на всі обов’язкові питання.
                </div>
            @endif

            <form action="{{ route('testing.session.submit', $session) }}" method="POST" id="testing-form">
                @csrf
                <input type="hidden" name="step" value="{{ $step }}">

                <article class="testing-card">
                    <div class="testing-card-header">
                        {{ $currentAttempt->test->title }}
                    </div>

                    <div class="testing-card-body">
                        @if($step === 1 && $currentAttempt->test->intro_text)
                            <div class="testing-intro">{{ $currentAttempt->test->intro_text }}</div>
                        @endif

                        <h2 class="testing-section-title">{{ $currentSection->title }}</h2>

                        @if($currentSection->instruction_text)
                            <div class="testing-instructions">{{ $currentSection->instruction_text }}</div>
                        @endif

                        @if($currentSection->description)
                            <div class="testing-section-text">
                                {{ $currentSection->description }}
                            </div>
                        @endif

                        @if($currentSection->media_type === 'youtube' && $currentSection->media_url)
                            <div class="ratio ratio-16x9 mb-4">
                                <iframe
                                    src="{{ str_contains($currentSection->media_url, 'watch?v=')
                                        ? str_replace('watch?v=', 'embed/', $currentSection->media_url)
                                        : $currentSection->media_url }}"
                                    title="{{ $currentSection->media_title ?? $currentSection->title }}"
                                    allowfullscreen>
                                </iframe>
                            </div>
                        @endif

                        @if($currentSection->media_type === 'audio' && $currentSection->media_url)
                            @php
                                $audioSrc = \Illuminate\Support\Str::startsWith($currentSection->media_url, ['http://', 'https://'])
                                    ? $currentSection->media_url
                                    : asset('storage/' . $currentSection->media_url);
                            @endphp

                            <div class="testing-question mb-4">
                                @if($currentSection->media_title)
                                    <div class="fw-semibold mb-2">{{ $currentSection->media_title }}</div>
                                @endif
                                <audio controls preload="metadata" class="w-100" src="{{ $audioSrc }}"></audio>
                            </div>
                        @endif

                        @foreach($displayQuestions as $question)
                            <fieldset class="testing-question">
                                <legend class="testing-question-title">
                                    {{ $loop->iteration }}. {{ $question->question_text }}
                                    @if($question->is_required)
                                        <span class="testing-required" aria-label="Обов’язкове питання">*</span>
                                    @endif
                                </legend>

                                @if($question->helper_text)
                                    <div class="text-muted small mb-3">{{ $question->helper_text }}</div>
                                @endif

                                @if(in_array($question->type, ['single_choice', 'true_false'], true))
                                    @foreach($question->options as $option)
                                        <div class="form-check testing-option">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="answers[{{ $question->id }}]"
                                                   value="{{ $option->id }}"
                                                   id="q{{ $question->id }}_{{ $option->id }}"
                                                   @checked((string) old("answers.{$question->id}") === (string) $option->id)
                                                   @required($question->is_required)>
                                            <label class="form-check-label"
                                                   for="q{{ $question->id }}_{{ $option->id }}">
                                                {{ $option->option_text }}
                                            </label>
                                        </div>
                                    @endforeach
                                @elseif($question->type === 'multiple_choice')
                                    @foreach($question->options as $option)
                                        <div class="form-check testing-option">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="answers[{{ $question->id }}][]"
                                                   value="{{ $option->id }}"
                                                   id="q{{ $question->id }}_{{ $option->id }}"
                                                   @checked(in_array((string) $option->id, array_map('strval', old("answers.{$question->id}", [])), true))>
                                            <label class="form-check-label"
                                                   for="q{{ $question->id }}_{{ $option->id }}">
                                                {{ $option->option_text }}
                                            </label>
                                        </div>
                                    @endforeach
                                @elseif($question->type === 'short_text')
                                    <input type="text"
                                           name="answers[{{ $question->id }}]"
                                           class="form-control"
                                           value="{{ old("answers.{$question->id}") }}"
                                           @required($question->is_required)>
                                @elseif($question->type === 'long_text')
                                    <textarea name="answers[{{ $question->id }}]"
                                              class="form-control"
                                              rows="4"
                                              @required($question->is_required)>{{ old("answers.{$question->id}") }}</textarea>
                                @endif
                            </fieldset>
                        @endforeach
                    </div>
                </article>

                <div class="testing-actions">
                    <button type="submit" class="btn-brand testing-submit">
                        {{ $step < $totalSteps ? 'Продовжити' : 'Завершити тестування' }}
                        <i class="bi {{ $step < $totalSteps ? 'bi-arrow-right' : 'bi-check-lg' }}"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($timeLimitMinutes) && !empty($startedAt))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const timerElement = document.getElementById('test-timer');
                const form = document.getElementById('testing-form');
                const startedAt = {{ $startedAt }} * 1000;
                const deadline = startedAt + ({{ (int) $timeLimitMinutes }} * 60 * 1000);
                let submitted = false;

                function updateTimer() {
                    const diff = deadline - Date.now();

                    if (diff <= 0) {
                        timerElement.textContent = '00:00';

                        if (form && !submitted) {
                            submitted = true;
                            form.submit();
                        }

                        return;
                    }

                    const totalSeconds = Math.floor(diff / 1000);
                    const minutes = Math.floor(totalSeconds / 60);
                    const seconds = totalSeconds % 60;

                    timerElement.textContent =
                        String(minutes).padStart(2, '0') + ':' +
                        String(seconds).padStart(2, '0');
                }

                updateTimer();
                setInterval(updateTimer, 1000);
            });
        </script>
    @endif
@endsection
