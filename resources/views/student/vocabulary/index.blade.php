@extends('public.layouts.main')

@section('content')
    <div class="vocabulary-page">
        <div class="container">
            <header class="vocabulary-header">
                <div>
                    <span class="vocabulary-eyebrow">
                        <i class="bi bi-translate" aria-hidden="true"></i>
                        Мої слова
                    </span>
                    <h1 class="vocabulary-title">Мій словник</h1>
                    <p class="vocabulary-subtitle">
                        Вивчайте слова зі своїх курсів та закріплюйте їх короткими повтореннями.
                    </p>
                </div>
                <a href="{{ route('courses.index') }}" class="btn-brand-outline">
                    <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                    Доступні курси
                </a>
            </header>

            @if(session('vocabulary_success'))
                <div class="alert alert-success" role="status">{{ session('vocabulary_success') }}</div>
            @endif

            <div class="vocabulary-toolbar">
                <nav class="vocabulary-modes" aria-label="Режим словника">
                    <a href="{{ route('student.vocabulary.learn', array_filter(['course' => $selectedCourseId])) }}"
                       class="vocabulary-mode {{ $mode === 'learn' ? 'is-active' : '' }}"
                       @if($mode === 'learn') aria-current="page" @endif>
                        <i class="bi bi-layers" aria-hidden="true"></i>
                        Вчити слова
                    </a>
                    <a href="{{ route('student.vocabulary.review', array_filter(['course' => $selectedCourseId, 'restart' => 1])) }}"
                       class="vocabulary-mode {{ $mode === 'review' ? 'is-active' : '' }}"
                       @if($mode === 'review') aria-current="page" @endif>
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        Повторювати
                    </a>
                </nav>

                @if($courses->isNotEmpty())
                    <form method="GET"
                          action="{{ $mode === 'learn' ? route('student.vocabulary.learn') : route('student.vocabulary.review') }}"
                          class="vocabulary-filter">
                        <label for="vocabulary-course">Курс</label>
                        <select id="vocabulary-course" name="course" class="form-select" onchange="this.form.submit()">
                            <option value="">Усі доступні курси</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected($selectedCourseId === $course->id)>
                                    {{ $course->title }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            <section class="vocabulary-stats" aria-label="Прогрес словника">
                <div class="vocabulary-stat">
                    <span>Усього</span>
                    <strong>{{ $stats['total'] }}</strong>
                </div>
                <div class="vocabulary-stat vocabulary-stat--new">
                    <span>Нові</span>
                    <strong>{{ $stats['new'] }}</strong>
                </div>
                <div class="vocabulary-stat vocabulary-stat--learning">
                    <span>Вивчаю</span>
                    <strong>{{ $stats['learning'] }}</strong>
                </div>
                <div class="vocabulary-stat vocabulary-stat--known">
                    <span>Вже знаю</span>
                    <strong>{{ $stats['known'] }}</strong>
                </div>
            </section>

            @if($mode === 'learn')
                @if($items->isNotEmpty())
                    <div class="vocabulary-grid">
                        @foreach($items as $item)
                            @php($progress = $item->userProgress->first())
                            <article class="vocabulary-card">
                                <div class="vocabulary-card-topline">
                                    <span class="vocabulary-language">{{ $item->language->name ?? 'Мова' }}</span>
                                    <span class="vocabulary-state">
                                        {{ $progress ? 'Вивчаю' : 'Нове слово' }}
                                    </span>
                                </div>

                                <div class="vocabulary-term">{{ $item->term }}</div>

                                @if($item->transcription || $item->part_of_speech)
                                    <div class="vocabulary-meta">
                                        @if($item->transcription)<span>{{ $item->transcription }}</span>@endif
                                        @if($item->part_of_speech)<span>{{ $item->part_of_speech }}</span>@endif
                                    </div>
                                @endif

                                <details class="vocabulary-reveal">
                                    <summary>
                                        <span>Показати переклад</span>
                                        <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                    </summary>
                                    <div class="vocabulary-reveal-content">
                                        <strong>{{ $item->translation }}</strong>

                                        @if($item->explanation)
                                            <p>{{ $item->explanation }}</p>
                                        @endif

                                        @if($item->example)
                                            <blockquote>
                                                {{ $item->example }}
                                                @if($item->example_translation)
                                                    <small>{{ $item->example_translation }}</small>
                                                @endif
                                            </blockquote>
                                        @endif
                                    </div>
                                </details>

                                <div class="vocabulary-card-actions">
                                    <form method="POST" action="{{ route('student.vocabulary.progress', $item) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="learning">
                                        @if($selectedCourseId)
                                            <input type="hidden" name="course" value="{{ $selectedCourseId }}">
                                        @endif
                                        <button type="submit" class="vocabulary-action vocabulary-action--later">
                                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i>
                                            Ще вчу
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('student.vocabulary.progress', $item) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="known">
                                        @if($selectedCourseId)
                                            <input type="hidden" name="course" value="{{ $selectedCourseId }}">
                                        @endif
                                        <button type="submit" class="vocabulary-action vocabulary-action--known">
                                            <i class="bi bi-check2" aria-hidden="true"></i>
                                            Вже знаю
                                        </button>
                                    </form>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{ $items->links() }}
                @else
                    <section class="vocabulary-empty">
                        @if($stats['total'] === 0)
                            <i class="bi bi-journal-plus" aria-hidden="true"></i>
                            <h2>Слів поки немає</h2>
                            <p>Вони з’являться тут, коли у доступних уроках буде додано словник.</p>
                            <a href="{{ route('courses.index') }}" class="btn-brand">
                                Доступні курси
                            </a>
                        @else
                            <i class="bi bi-stars" aria-hidden="true"></i>
                            <h2>{{ $selectedCourseId ? 'У цьому курсі немає нових слів' : 'Усі доступні слова вже вивчені' }}</h2>
                            <p>Перейдіть до повторення, щоб перевірити себе.</p>
                            <a href="{{ route('student.vocabulary.review', array_filter(['course' => $selectedCourseId, 'restart' => 1])) }}"
                               class="btn-brand">
                                Повторити слова
                            </a>
                        @endif
                    </section>
                @endif
            @else
                @php($reviewResult = session('vocabulary_review_result'))

                @if($reviewResult)
                    <section class="vocabulary-feedback {{ $reviewResult['correct'] ? 'is-correct' : 'is-incorrect' }}"
                             aria-live="polite">
                        <i class="bi {{ $reviewResult['correct'] ? 'bi-check-circle' : 'bi-x-circle' }}" aria-hidden="true"></i>
                        <div>
                            <strong>{{ $reviewResult['correct'] ? 'Правильно!' : 'Потрібно повторити' }}</strong>
                            <p>
                                {{ $reviewResult['term'] }} — {{ $reviewResult['translation'] }}
                                @unless($reviewResult['correct'])
                                    <span>Ваша відповідь: {{ $reviewResult['selected'] }}</span>
                                @endunless
                            </p>
                            @if($reviewResult['example'])
                                <small>{{ $reviewResult['example'] }}</small>
                            @endif
                        </div>
                    </section>
                @endif

                @if($question && $options->count() >= 2)
                    <section class="vocabulary-quiz">
                        <div class="vocabulary-quiz-progress">Повторено в цьому підході: {{ $reviewedCount }}</div>
                        <span class="vocabulary-quiz-label">Оберіть правильний переклад</span>
                        <h2>{{ $question->term }}</h2>

                        @if($question->transcription || $question->part_of_speech)
                            <div class="vocabulary-meta vocabulary-meta--center">
                                @if($question->transcription)<span>{{ $question->transcription }}</span>@endif
                                @if($question->part_of_speech)<span>{{ $question->part_of_speech }}</span>@endif
                            </div>
                        @endif

                        <form method="POST"
                              action="{{ route('student.vocabulary.review.submit', $question) }}"
                              class="vocabulary-options">
                            @csrf
                            @if($selectedCourseId)
                                <input type="hidden" name="course" value="{{ $selectedCourseId }}">
                            @endif

                            @foreach($options as $option)
                                <label class="vocabulary-option">
                                    <input type="radio" name="selected_id" value="{{ $option->id }}" required>
                                    <span>{{ $option->translation }}</span>
                                </label>
                            @endforeach

                            <button type="submit" class="btn-brand vocabulary-submit">
                                Перевірити
                                <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </button>
                        </form>
                    </section>
                @elseif($question)
                    <section class="vocabulary-empty">
                        <i class="bi bi-translate" aria-hidden="true"></i>
                        <h2>Потрібно щонайменше два слова</h2>
                        <p>Варіанти перекладу з’являться, коли у ваших доступних уроках буде хоча б два різні слова.</p>
                        <a href="{{ route('student.vocabulary.learn', array_filter(['course' => $selectedCourseId])) }}"
                           class="btn-brand">
                            Вчити слова
                        </a>
                    </section>
                @else
                    <section class="vocabulary-empty">
                        <i class="bi bi-trophy" aria-hidden="true"></i>
                        <h2>{{ $reviewedCount ? 'Повторення завершено' : 'Поки немає слів для повторення' }}</h2>
                        <p>
                            {{ $reviewedCount
                                ? 'Ви пройшли всі вибрані слова цього підходу.'
                                : 'Спочатку відкрийте картки та позначте знайомі слова.' }}
                        </p>
                        @if($reviewedCount)
                            <a href="{{ route('student.vocabulary.review', array_filter(['course' => $selectedCourseId, 'restart' => 1])) }}"
                               class="btn-brand">
                                Повторити ще раз
                            </a>
                        @else
                            <a href="{{ route('student.vocabulary.learn', array_filter(['course' => $selectedCourseId])) }}"
                               class="btn-brand">
                                Вчити слова
                            </a>
                        @endif
                    </section>
                @endif
            @endif
        </div>
    </div>
@endsection
