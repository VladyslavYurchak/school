@extends('public.layouts.main')

@section('content')
    @php
        $languageLabels = [
            'en' => 'англійської',
            'fr' => 'французької',
            'zh' => 'китайської',
        ];
        $languageLabel = $languageLabels[$session->language_code] ?? 'іноземної';
        $score = round((float) $session->total_weighted_score);
        $attempts = $session->attempts
            ->sortBy(fn ($attempt) => [$attempt->test->sort_order ?? 0, $attempt->id]);
        $reviewAttempts = $attempts
            ->filter(fn ($attempt) => $attempt->test?->show_result_immediately);
    @endphp

    <div class="container testing-page">
        <div class="testing-shell">
            @if(session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif

            <section class="testing-result-card">
                <div class="testing-result-hero">
                    <div class="testing-kicker">Тестування завершено</div>
                    <h1 class="h2 mt-2 mb-1">Ваш орієнтовний рівень {{ $languageLabel }} мови</h1>
                    <div class="testing-result-level">{{ $session->detected_level ?? '—' }}</div>
                    <p class="mb-0 text-muted">
                        Загальний результат: <strong>{{ $score }} зі 100 балів</strong>
                    </p>

                    @if($session->resultRange)
                        <div class="mt-4">
                            <h2 class="h5">{{ $session->resultRange->title }}</h2>
                            @if($session->resultRange->description)
                                <p class="mb-2">{{ $session->resultRange->description }}</p>
                            @endif
                            @if($session->resultRange->recommendation_text)
                                <p class="text-muted mb-0">{{ $session->resultRange->recommendation_text }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            </section>

            @if($reviewAttempts->isNotEmpty())
                <section class="testing-result-card testing-review">
                    <div class="testing-card-header">Розбір відповідей</div>

                    @foreach($reviewAttempts as $attempt)
                        @php
                            $answersByQuestionId = $attempt->answers->keyBy('question_id');
                        @endphp

                        @if($attempt->test)
                            <div class="testing-card-body pb-0">
                                <h2 class="h5 mb-0">{{ $attempt->test->title }}</h2>
                            </div>

                            @foreach($attempt->test->sections->where('is_active', true) as $section)
                                @if($section->questions->where('is_active', true)->isNotEmpty())
                                    <div class="testing-card-body pb-1">
                                        <h3 class="h6 mb-0">{{ $section->title }}</h3>
                                    </div>

                                    @foreach($section->questions->where('is_active', true) as $question)
                                        @php
                                            $answer = $answersByQuestionId->get($question->id);
                                            $correctOptions = $question->options->where('is_correct', true);
                                            $selectedIds = [];

                                            if ($question->type === 'multiple_choice' && !empty($answer?->answer_text)) {
                                                $decoded = json_decode($answer->answer_text, true);
                                                $selectedIds = is_array($decoded) ? $decoded : [];
                                            }
                                        @endphp

                                        <div class="testing-review-question">
                                            <div class="d-flex align-items-start justify-content-between gap-3 mb-2">
                                                <div class="fw-semibold">{{ $question->question_text }}</div>
                                                @if($answer?->is_correct === true)
                                                    <span class="badge text-bg-success">Правильно</span>
                                                @elseif($answer)
                                                    <span class="badge text-bg-danger">Помилка</span>
                                                @else
                                                    <span class="badge text-bg-secondary">Без відповіді</span>
                                                @endif
                                            </div>

                                            @if(in_array($question->type, ['single_choice', 'true_false'], true))
                                                @php
                                                    $selectedOption = $answer?->selectedOption;
                                                    if (!$selectedOption && $answer?->answer_text) {
                                                        $selectedOption = $question->options->firstWhere('id', $answer->answer_text);
                                                    }
                                                @endphp
                                                <div class="small"><strong>Ваша відповідь:</strong> {{ $selectedOption?->option_text ?? '—' }}</div>
                                                <div class="small text-success"><strong>Правильна:</strong> {{ $correctOptions->pluck('option_text')->join(', ') ?: '—' }}</div>
                                            @elseif($question->type === 'multiple_choice')
                                                @php
                                                    $selectedOptions = $question->options->whereIn('id', $selectedIds);
                                                @endphp
                                                <div class="small"><strong>Ваша відповідь:</strong> {{ $selectedOptions->pluck('option_text')->join(', ') ?: '—' }}</div>
                                                <div class="small text-success"><strong>Правильні:</strong> {{ $correctOptions->pluck('option_text')->join(', ') ?: '—' }}</div>
                                            @else
                                                <div class="small"><strong>Ваша відповідь:</strong> {{ $answer?->answer_text ?? '—' }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </section>
            @endif

            <section class="testing-result-card testing-lead-form">
                <h2 class="h4 text-center mb-2">Підберемо навчання під ваш рівень</h2>
                <p class="text-muted text-center mb-4">Залиште ім’я та хоча б один зручний спосіб зв’язку.</p>

                @if($errors->any())
                    <div class="alert alert-danger" role="alert">
                        Перевірте контактні дані та підтвердьте згоду на їх обробку.
                    </div>
                @endif

                <form action="{{ route('testing.session.lead.store', $session) }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="lead-name">Ім’я</label>
                            <input type="text" id="lead-name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $session->lead->name ?? '') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead-phone">Телефон</label>
                            <input type="tel" id="lead-phone" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $session->lead->phone ?? '') }}"
                                   autocomplete="tel">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead-email">Email</label>
                            <input type="email" id="lead-email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $session->lead->email ?? '') }}"
                                   autocomplete="email">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="lead-telegram">Telegram</label>
                            <input type="text" id="lead-telegram" name="telegram"
                                   class="form-control @error('telegram') is-invalid @enderror"
                                   value="{{ old('telegram', $session->lead->telegram ?? '') }}"
                                   placeholder="@username">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" for="lead-age">Вік</label>
                            <input type="number" id="lead-age" name="age"
                                   class="form-control @error('age') is-invalid @enderror"
                                   value="{{ old('age', $session->lead->age ?? '') }}"
                                   min="1" max="120">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" for="lead-format">Бажаний формат</label>
                            <select id="lead-format" name="preferred_study_format" class="form-select">
                                <option value="">Ще не визначився / не визначилась</option>
                                @foreach(['Індивідуально', 'У парі', 'У групі', 'Онлайн-курс'] as $format)
                                    <option value="{{ $format }}" @selected(old('preferred_study_format', $session->lead->preferred_study_format ?? '') === $format)>
                                        {{ $format }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" for="lead-notes">Коментар</label>
                            <textarea id="lead-notes" name="notes" class="form-control" rows="3">{{ old('notes', $session->lead->notes ?? '') }}</textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input @error('contact_consent') is-invalid @enderror"
                                       type="checkbox" name="contact_consent" value="1"
                                       id="contact-consent"
                                       @checked(old('contact_consent', $session->lead->contact_consent ?? false))
                                       required>
                                <label class="form-check-label" for="contact-consent">
                                    Погоджуюсь на обробку контактних даних
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="testing-actions">
                        <button type="submit" class="btn-brand testing-submit">
                            Надіслати контакти
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
