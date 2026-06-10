@extends('public.layouts.main')

@section('content')
    <div class="container py-5">

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @php
            $languageLabels = [
                'en' => 'англійської',
                'fr' => 'французької',
                'zh' => 'китайської',
            ];

            $languageLabel = $languageLabels[$session->language_code] ?? 'іноземної';

            $score = round((float) $session->total_weighted_score, 0);
            $max = 100;
        @endphp

        {{-- RESULT --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-5 text-center">

                <h1 class="mb-4">Результат тестування</h1>

                <h2 class="mb-3">
                    Ваш рівень {{ $languageLabel }}:
                    <span class="fw-bold text-primary">
                        {{ $session->detected_level ?? '—' }}
                    </span>
                </h2>

                <div class="fs-5 mb-3">
                    Ви набрали <strong>{{ $score }}</strong> балів зі <strong>{{ $max }}</strong>
                </div>

                @if($session->resultRange)
                    <div class="mt-4">
                        <h4 class="fw-semibold">
                            {{ $session->resultRange->title }}
                        </h4>

                        @if($session->resultRange->description)
                            <p class="mt-2 mb-2">
                                {{ $session->resultRange->description }}
                            </p>
                        @endif

                        @if($session->resultRange->recommendation_text)
                            <p class="text-muted mb-0">
                                {{ $session->resultRange->recommendation_text }}
                            </p>
                        @endif
                    </div>
                @endif

            </div>
        </div>

            @php
                $attempt = $session->attempts->first();
                $answersByQuestionId = $attempt
                    ? $attempt->answers->keyBy('question_id')
                    : collect();
            @endphp

            @if($attempt && $attempt->test)

                <div class="text-center mb-3 text-muted">
                    Якщо цікаво — ось правильні відповіді до всього тесту 🙂
                </div>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header">
                        Детальний розбір тесту
                    </div>

                    <div class="card-body">
                        @foreach($attempt->test->sections as $section)
                            <div class="mb-5">
                                <h4 class="mb-3">{{ $section->title }}</h4>

                                @foreach($section->questions as $question)
                                    @php
                                        $answer = $answersByQuestionId->get($question->id);
                                        $correctOptions = $question->options->where('is_correct', true);
                                        $selectedIds = [];

                                        if ($question->type === 'multiple_choice' && !empty($answer?->answer_text)) {
                                            $decoded = json_decode($answer->answer_text, true);
                                            $selectedIds = is_array($decoded) ? $decoded : [];
                                        }
                                    @endphp

                                    <div class="border rounded p-3 mb-3">

                                        <div class="d-flex justify-content-between mb-2">
                                            <div class="fw-semibold">
                                                {{ $question->question_text }}
                                            </div>

                                            <div>
                                                @if($answer)
                                                    @if($answer->is_correct)
                                                        <span class="badge bg-success">✔</span>
                                                    @else
                                                        <span class="badge bg-danger">✖</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">—</span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- SINGLE --}}
                                        @if(in_array($question->type, ['single_choice', 'true_false']))
                                            <div class="small">
                                                <strong>Ваша:</strong>
                                                @php
                                                    $selectedOption = $answer?->selectedOption;

                                                    if (!$selectedOption && $answer?->answer_text) {
                                                        $selectedOption = $question->options->firstWhere('id', $answer->answer_text);
                                                    }
                                                @endphp

                                                {{ $selectedOption?->option_text ?? '—' }}                                            </div>

                                            <div class="small text-success">
                                                <strong>Правильна:</strong>
                                                {{ $correctOptions->pluck('option_text')->join(', ') ?: '—' }}
                                            </div>
                                        @endif

                                        {{-- MULTIPLE --}}
                                        @if($question->type === 'multiple_choice')
                                            <div class="small">
                                                <strong>Ваша:</strong>
                                                @php
                                                    $selectedOptions = $question->options->whereIn('id', $selectedIds);
                                                @endphp

                                                {{ $selectedOptions->pluck('option_text')->join(', ') ?: '—' }}
                                            </div>

                                            <div class="small text-success">
                                                <strong>Правильні:</strong>
                                                {{ $correctOptions->pluck('option_text')->join(', ') ?: '—' }}
                                            </div>
                                        @endif

                                        {{-- TEXT --}}
                                        @if(in_array($question->type, ['short_text', 'long_text']))
                                            <div class="small">
                                                <strong>Ваша відповідь:</strong>
                                                {{ $answer?->answer_text ?? '—' }}
                                            </div>
                                        @endif

                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        {{-- LEAD FORM --}}
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">

                <h3 class="mb-3 text-center">
                    Залиште контакти — ми підберемо вам навчання
                </h3>

                <form action="{{ route('testing.session.lead.store', $session) }}" method="POST">
                    @csrf

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Ім’я</label>
                            <input type="text"
                                   name="name"
                                   class="form-control"
                                   value="{{ old('name', $session->lead->name ?? '') }}"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="text"
                                   name="phone"
                                   class="form-control"
                                   value="{{ old('phone', $session->lead->phone ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   value="{{ old('email', $session->lead->email ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telegram</label>
                            <input type="text"
                                   name="telegram"
                                   class="form-control"
                                   value="{{ old('telegram', $session->lead->telegram ?? '') }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Вік</label>
                            <input type="number"
                                   name="age"
                                   class="form-control"
                                   value="{{ old('age', $session->lead->age ?? '') }}">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Формат навчання</label>
                            <input type="text"
                                   name="preferred_study_format"
                                   class="form-control"
                                   value="{{ old('preferred_study_format', $session->lead->preferred_study_format ?? '') }}"
                                   placeholder="Індивідуально, у групі, онлайн...">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Коментар</label>
                            <textarea name="notes"
                                      class="form-control"
                                      rows="3">{{ old('notes', $session->lead->notes ?? '') }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input type="hidden" name="contact_consent" value="0">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="contact_consent"
                                       value="1"
                                       id="contact_consent"
                                    @checked(old('contact_consent', $session->lead->contact_consent ?? false))>

                                <label class="form-check-label" for="contact_consent">
                                    Я погоджуюсь на обробку даних
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-register px-4">
                            Надіслати контакти
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
@endsection
