@extends('index.layouts.main')

@section('content')
    <div class="container py-4">

        <div class="text-center mb-4">
            <h1>Тестування на визначення рівня іноземної мови</h1>
            <p class="text-muted mb-1">
                Частина {{ $step }} з {{ $totalSteps }}
            </p>
        </div>

        @if(!empty($timeLimitMinutes))
            <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4">
                <span>Час на тестування:</span>
                <strong id="test-timer">--:--</strong>
            </div>
        @endif

        <div class="mb-4">
            <div class="progress" style="height: 10px;">
                <div class="progress-bar" role="progressbar"
                     style="width: {{ ($step / $totalSteps) * 100 }}%;"
                     aria-valuenow="{{ ($step / $totalSteps) * 100 }}"
                     aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>

        <form action="{{ route('testing.session.submit', $session) }}" method="POST">
            @csrf
            <input type="hidden" name="step" value="{{ $step }}">

            <div class="card mb-4">
                <div class="card-header">
                    <strong>{{ $currentAttempt->test->title }}</strong>
                </div>

                <div class="card-body">

                    <div class="mb-4">
                        <h4>{{ $currentSection->title }}</h4>

                        @if($currentSection->instruction_text)
                            <p class="text-muted">{{ $currentSection->instruction_text }}</p>
                        @endif

                        @if($currentSection->media_type === 'youtube' && $currentSection->media_url)
                            <div class="ratio ratio-16x9 mb-3">
                                <iframe
                                    src="{{ str_contains($currentSection->media_url, 'watch?v=')
                    ? str_replace('watch?v=', 'embed/', $currentSection->media_url)
                    : $currentSection->media_url }}"
                                    title="{{ $currentSection->media_title ?? $currentSection->title }}"
                                    allowfullscreen>
                                </iframe>
                            </div>
                        @endif

                        @foreach($currentSection->questions as $question)
                            <div class="mb-4 p-3 border rounded">
                                <div class="fw-semibold mb-2">
                                    {{ $question->question_text }}
                                </div>

                                @if($question->helper_text)
                                    <div class="text-muted small mb-2">
                                        {{ $question->helper_text }}
                                    </div>
                                @endif

                                @if($question->type === 'single_choice' || $question->type === 'true_false')
                                    @foreach($question->options as $option)
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="radio"
                                                   name="answers[{{ $question->id }}]"
                                                   value="{{ $option->id }}"
                                                   id="q{{ $question->id }}_{{ $option->id }}"
                                                   required>
                                            <label class="form-check-label"
                                                   for="q{{ $question->id }}_{{ $option->id }}">
                                                {{ $option->option_text }}
                                            </label>
                                        </div>
                                    @endforeach
                                @elseif($question->type === 'multiple_choice')
                                    @foreach($question->options as $option)
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="answers[{{ $question->id }}][]"
                                                   value="{{ $option->id }}"
                                                   id="q{{ $question->id }}_{{ $option->id }}">
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
                                           required>
                                @elseif($question->type === 'long_text')
                                    <textarea name="answers[{{ $question->id }}]"
                                              class="form-control"
                                              rows="4"
                                              required></textarea>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-register">
                    {{ $step < $totalSteps ? 'Далі' : 'Завершити тестування' }}
                </button>
            </div>
        </form>

    </div>

    @if(!empty($timeLimitMinutes) && !empty($startedAt))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const timerElement = document.getElementById('test-timer');
                const form = document.querySelector('form');

                const startedAt = {{ $startedAt }} * 1000;
                const limitMinutes = {{ (int) $timeLimitMinutes }};
                const deadline = startedAt + (limitMinutes * 60 * 1000);

                function updateTimer() {
                    const now = Date.now();
                    const diff = deadline - now;

                    if (diff <= 0) {
                        timerElement.textContent = '00:00';

                        if (form) {
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
