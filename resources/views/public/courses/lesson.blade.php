@extends('public.layouts.main')

@section('title', $lesson->title . ' | ' . $course->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags($lesson->description ?: $course->description), 155))
@section('robots', 'noindex, nofollow, noarchive')

@section('content')
    <div class="lesson-page">
        <header class="lesson-header">
            <div class="container">
                <a href="{{ route('courses.show', $course) }}" class="lesson-back">
                    <i class="bi bi-arrow-left"></i>
                    {{ $course->title }}
                </a>
                <div class="lesson-heading-row">
                    <div>
                        <div class="lesson-kicker">Урок {{ $lesson->position }}</div>
                        <h1 class="lesson-title">{{ $lesson->title }}</h1>
                        @if($lesson->description)
                            <p class="lesson-description">{{ $lesson->description }}</p>
                        @endif
                    </div>
                    <span class="lesson-progress-label">
                        {{ $course->lessons->search(fn ($item) => $item->id === $lesson->id) + 1 }} / {{ $course->lessons->count() }}
                    </span>
                </div>
            </div>
        </header>

        <div class="container">
            <div class="lesson-layout">
                <main class="lesson-main">
                    <div class="lesson-materials" aria-label="Матеріали уроку">
                        @forelse($lesson->contentBlocks as $block)
                            <section class="lesson-material-block lesson-material-block--{{ $block->type }}">
                                <header class="lesson-material-header">
                                    <span class="lesson-material-icon" aria-hidden="true">
                                        <i class="bi {{ match($block->type) {
                                            'video' => 'bi-play-circle',
                                            'audio' => 'bi-volume-up',
                                            'image' => 'bi-image',
                                            'pdf' => 'bi-file-earmark-pdf',
                                            default => 'bi-text-paragraph',
                                        } }}"></i>
                                    </span>
                                    <div>
                                        <div class="lesson-material-kind">
                                            {{ match($block->type) {
                                                'video' => 'Відео',
                                                'audio' => 'Аудіо',
                                                'image' => 'Зображення',
                                                'pdf' => 'PDF-матеріал',
                                                default => 'Матеріал',
                                            } }}
                                        </div>
                                        @if($block->title)
                                            <h2 class="lesson-material-title">{{ $block->title }}</h2>
                                        @endif
                                    </div>
                                </header>

                                @if($block->type === 'video')
                                    <div class="lesson-video-frame ratio ratio-16x9">
                                        <iframe
                                            src="{{ $block->video_url }}"
                                            title="{{ $block->title ?: 'Відео уроку' }}"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen
                                        ></iframe>
                                    </div>
                                @elseif($block->type === 'audio')
                                    <div class="lesson-audio-player">
                                        <audio controls preload="metadata" src="{{ asset('storage/' . $block->media_path) }}"></audio>
                                        <a href="{{ asset('storage/' . $block->media_path) }}"
                                           class="lesson-download-link" download>
                                            <i class="bi bi-download"></i>
                                            Завантажити аудіо
                                        </a>
                                    </div>
                                @elseif($block->type === 'image')
                                    <a href="{{ asset('storage/' . $block->media_path) }}"
                                       class="lesson-image-link" target="_blank" rel="noopener">
                                        <img src="{{ asset('storage/' . $block->media_path) }}"
                                             alt="{{ $block->title ?: $block->media_name }}"
                                             class="lesson-material-image" loading="lazy">
                                    </a>
                                @elseif($block->type === 'pdf')
                                    <div class="lesson-pdf-preview">
                                        <iframe src="{{ asset('storage/' . $block->media_path) }}"
                                                title="{{ $block->title ?: $block->media_name }}"></iframe>
                                    </div>
                                    <div class="lesson-file-actions">
                                        <a href="{{ asset('storage/' . $block->media_path) }}"
                                           class="lesson-file-button" target="_blank" rel="noopener">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                            Відкрити PDF
                                        </a>
                                        <a href="{{ asset('storage/' . $block->media_path) }}"
                                           class="lesson-download-link" download>
                                            <i class="bi bi-download"></i>
                                            Завантажити
                                        </a>
                                    </div>
                                @endif

                                @if($block->content)
                                    <div class="lesson-prose {{ $block->type === 'text' ? '' : 'lesson-prose--description' }}">
                                        {!! $block->content !!}
                                    </div>
                                @endif
                            </section>
                        @empty
                            @if($lesson->content || $lesson->video_url || $lesson->audio_file || !empty($lesson->media_files))
                                @if($lesson->video_url)
                                    <section class="lesson-material-block lesson-material-block--video">
                                        <div class="lesson-video-frame ratio ratio-16x9">
                                            <iframe src="{{ $lesson->video_url }}" title="Відео уроку" allowfullscreen></iframe>
                                        </div>
                                    </section>
                                @endif

                                @if($lesson->audio_file)
                                    <section class="lesson-material-block lesson-material-block--audio">
                                        <audio controls preload="metadata" src="{{ asset('storage/' . $lesson->audio_file) }}"></audio>
                                    </section>
                                @endif

                                @if($lesson->content)
                                    <section class="lesson-material-block lesson-material-block--text">
                                        <div class="lesson-prose">{!! $lesson->content !!}</div>
                                    </section>
                                @endif
                            @else
                                <div class="lesson-empty-materials">
                                    <i class="bi bi-journal-text"></i>
                                    <p>Матеріали цього уроку ще готуються.</p>
                                </div>
                            @endif
                        @endforelse
                    </div>

                    @if($lesson->vocabularyItems->isNotEmpty())
                        <section class="lesson-system-block lesson-system-block--vocabulary">
                            <div class="lesson-system-heading">
                                <span class="lesson-system-icon"><i class="bi bi-translate"></i></span>
                                <div>
                                    <div class="lesson-system-kicker">Слова до уроку</div>
                                    <h2>Словник</h2>
                                </div>
                            </div>

                            <div class="lesson-vocabulary-grid">
                                @foreach($lesson->vocabularyItems as $item)
                                    <article class="lesson-vocabulary-card">
                                        <div class="lesson-vocabulary-card-header">
                                            <div>
                                                <h3>{{ $item->term }}</h3>
                                                <div class="lesson-vocabulary-translation">{{ $item->translation }}</div>
                                            </div>

                                            @if($item->pivot->is_required)
                                                <span class="lesson-vocabulary-badge">Обов’язково</span>
                                            @endif
                                        </div>

                                        @if($item->transcription || $item->part_of_speech)
                                            <div class="lesson-vocabulary-meta">
                                                @if($item->transcription)
                                                    <span>{{ $item->transcription }}</span>
                                                @endif

                                                @if($item->part_of_speech)
                                                    <span>{{ $item->part_of_speech }}</span>
                                                @endif
                                            </div>
                                        @endif

                                        @if($item->explanation)
                                            <p class="lesson-vocabulary-explanation">{{ $item->explanation }}</p>
                                        @endif

                                        @if($item->example)
                                            <blockquote class="lesson-vocabulary-example">
                                                <span>{{ $item->example }}</span>
                                                @if($item->example_translation)
                                                    <small>{{ $item->example_translation }}</small>
                                                @endif
                                            </blockquote>
                                        @endif

                                        @if($item->pivot->note)
                                            <p class="lesson-vocabulary-note">{{ $item->pivot->note }}</p>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @foreach($lesson->exercises as $exercise)
                        @if($exercise->type === \App\Models\LessonExercise::TYPE_MATCHING)
                            <section class="lesson-system-block lesson-system-block--exercise"
                                     data-matching-exercise
                                     data-exercise-id="{{ $exercise->id }}">
                                <div class="lesson-system-heading">
                                    <span class="lesson-system-icon"><i class="bi bi-intersect"></i></span>
                                    <div>
                                        <div class="lesson-system-kicker">Інтерактивна вправа</div>
                                        <h2>{{ $exercise->title }}</h2>
                                    </div>
                                </div>

                                @if($exercise->description)
                                    <p class="lesson-exercise-description">{{ $exercise->description }}</p>
                                @endif

                                <div class="lesson-matching-status" aria-live="polite">
                                    <span data-matching-progress>З’єднано: 0 / {{ $exercise->items->count() }}</span>
                                    <button type="button" class="lesson-matching-reset" data-matching-reset>
                                        <i class="bi bi-shuffle"></i> Перемішати
                                    </button>
                                </div>

                                <div class="lesson-matching-board">
                                    <div class="lesson-matching-column" data-matching-prompts>
                                        @foreach($exercise->items as $item)
                                            <button type="button" class="lesson-matching-option"
                                                    data-pair-id="{{ $item->id }}">
                                                {{ $item->prompt }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <div class="lesson-matching-column" data-matching-answers>
                                        @foreach($exercise->items as $item)
                                            <button type="button" class="lesson-matching-option"
                                                    data-pair-id="{{ $item->id }}">
                                                {{ $item->answer }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="lesson-matching-complete" data-matching-complete hidden>
                                    <i class="bi bi-check2-circle"></i>
                                    <strong>Готово! Усі пари з’єднано правильно.</strong>
                                </div>
                            </section>
                        @elseif($exercise->type === \App\Models\LessonExercise::TYPE_FILL_BLANK)
                            @php($answerMode = data_get($exercise->settings, 'answer_mode', 'typing'))
                            <section class="lesson-system-block lesson-system-block--fill-blank"
                                     data-fill-blank-exercise
                                     data-exercise-id="{{ $exercise->id }}">
                                <div class="lesson-system-heading">
                                    <span class="lesson-system-icon"><i class="bi bi-input-cursor-text"></i></span>
                                    <div>
                                        <div class="lesson-system-kicker">Інтерактивна вправа</div>
                                        <h2>{{ $exercise->title }}</h2>
                                    </div>
                                </div>

                                @if($exercise->description)
                                    <p class="lesson-exercise-description">{{ $exercise->description }}</p>
                                @endif

                                <div class="lesson-fill-blank-list">
                                    @foreach($exercise->items as $item)
                                        @php([$beforeBlank, $afterBlank] = explode('___', $item->prompt, 2))
                                        <div class="lesson-fill-blank-item"
                                             data-fill-blank-item
                                             data-answer="{{ $item->answer }}">
                                            <span class="lesson-fill-blank-number">{{ $loop->iteration }}</span>
                                            <div class="lesson-fill-blank-sentence">
                                                <span>{{ $beforeBlank }}</span>
                                                @if($answerMode === \App\Models\LessonExercise::ANSWER_MODE_CHOICE)
                                                    <select class="lesson-fill-blank-control" data-fill-blank-control
                                                            aria-label="{{ $exercise->title }}: відповідь до завдання {{ $loop->iteration }}">
                                                        <option value="">Оберіть...</option>
                                                        @foreach($exercise->items as $optionItem)
                                                            <option value="{{ $optionItem->answer }}">{{ $optionItem->answer }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <input type="text" class="lesson-fill-blank-control"
                                                           data-fill-blank-control autocomplete="off"
                                                           aria-label="{{ $exercise->title }}: відповідь до завдання {{ $loop->iteration }}">
                                                @endif
                                                <span>{{ $afterBlank }}</span>
                                            </div>
                                            <span class="lesson-fill-blank-feedback" data-fill-blank-feedback aria-live="polite"></span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="lesson-fill-blank-actions">
                                    <button type="button" class="lesson-submit-button" data-fill-blank-check>
                                        <i class="bi bi-check2-circle"></i> Перевірити
                                    </button>
                                    <button type="button" class="lesson-matching-reset" data-fill-blank-reset>
                                        <i class="bi bi-arrow-counterclockwise"></i> Спробувати ще раз
                                    </button>
                                    <strong class="lesson-fill-blank-result" data-fill-blank-result aria-live="polite"></strong>
                                </div>
                            </section>
                        @elseif($exercise->type === \App\Models\LessonExercise::TYPE_WORD_ORDER)
                            <section class="lesson-system-block lesson-system-block--word-order"
                                     data-word-order-exercise
                                     data-exercise-id="{{ $exercise->id }}">
                                <div class="lesson-system-heading">
                                    <span class="lesson-system-icon"><i class="bi bi-sort-down"></i></span>
                                    <div>
                                        <div class="lesson-system-kicker">Інтерактивна вправа</div>
                                        <h2>{{ $exercise->title }}</h2>
                                    </div>
                                </div>

                                @if($exercise->description)
                                    <p class="lesson-exercise-description">{{ $exercise->description }}</p>
                                @endif

                                <div class="lesson-word-order-list">
                                    @foreach($exercise->items as $item)
                                        @php($tokens = preg_split('/\s+/u', trim($item->answer), -1, PREG_SPLIT_NO_EMPTY))
                                        <div class="lesson-word-order-item"
                                             data-word-order-item
                                             data-answer="{{ $item->answer }}">
                                            <div class="lesson-word-order-item-header">
                                                <span class="lesson-fill-blank-number">{{ $loop->iteration }}</span>
                                                @if($item->prompt)
                                                    <p>{{ $item->prompt }}</p>
                                                @else
                                                    <p>Складіть правильне речення</p>
                                                @endif
                                            </div>

                                            <div class="lesson-word-order-selected"
                                                 data-word-order-selected
                                                 aria-label="{{ $exercise->title }}: складене речення {{ $loop->iteration }}">
                                                <span class="lesson-word-order-placeholder">Натискайте слова нижче</span>
                                            </div>

                                            <div class="lesson-word-order-bank" data-word-order-bank>
                                                @foreach($tokens as $tokenIndex => $token)
                                                    <button type="button" class="lesson-word-order-token"
                                                            data-token-index="{{ $tokenIndex }}">
                                                        {{ $token }}
                                                    </button>
                                                @endforeach
                                            </div>

                                            <span class="lesson-word-order-feedback"
                                                  data-word-order-feedback aria-live="polite"></span>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="lesson-fill-blank-actions">
                                    <button type="button" class="lesson-submit-button" data-word-order-check>
                                        <i class="bi bi-check2-circle"></i> Перевірити
                                    </button>
                                    <button type="button" class="lesson-matching-reset" data-word-order-reset>
                                        <i class="bi bi-arrow-counterclockwise"></i> Спробувати ще раз
                                    </button>
                                    <strong class="lesson-fill-blank-result" data-word-order-result aria-live="polite"></strong>
                                </div>
                            </section>
                        @elseif(in_array($exercise->type, [
                            \App\Models\LessonExercise::TYPE_TRANSFORMATION,
                            \App\Models\LessonExercise::TYPE_DICTATION,
                        ], true))
                            @include('public.courses.partials.text-answer-exercise', ['exercise' => $exercise])
                        @elseif($exercise->type === \App\Models\LessonExercise::TYPE_TRUE_FALSE)
                            @include('public.courses.partials.true-false-exercise', ['exercise' => $exercise])
                        @endif
                    @endforeach

                    @if($lesson->tests->count())
                        <section class="lesson-system-block lesson-system-block--tests">
                            <div class="lesson-system-heading">
                                <span class="lesson-system-icon"><i class="bi bi-ui-checks"></i></span>
                                <div>
                                    <div class="lesson-system-kicker">Перевірка знань</div>
                                    <h2>Підсумковий тест</h2>
                                </div>
                            </div>

                            @if($lastTestAttempt)
                                <div class="lesson-test-result {{ $lastTestAttempt->passed ? 'is-passed' : 'is-failed' }}">
                                    <strong>Останній результат: {{ $lastTestAttempt->score }} / {{ $lastTestAttempt->max_score }}</strong>
                                    <span>{{ $lastTestAttempt->percent }}% · {{ $lastTestAttempt->finished_at?->format('d.m.Y H:i') }}</span>
                                </div>
                            @endif

                            <form action="{{ route('courses.lessons.tests.submit', [$course, $lesson]) }}" method="POST">
                                @csrf
                                <div class="lesson-tests">
                                    @foreach($lesson->tests as $i => $test)
                                        <fieldset class="lesson-test-item">
                                            <legend class="lesson-test-question">
                                                <span class="lesson-test-num">{{ $i + 1 }}</span>
                                                <span>{{ $test->question }}</span>
                                            </legend>

                                            @if($test->options->count())
                                                <div class="lesson-test-options">
                                                    @foreach($test->options as $option)
                                                        <label class="lesson-test-option">
                                                            <input type="{{ $test->is_multiple_choice ? 'checkbox' : 'radio' }}"
                                                                   name="answers[{{ $test->id }}]{{ $test->is_multiple_choice ? '[]' : '' }}"
                                                                   value="{{ $option->id }}">
                                                            <span>{{ $option->option_text }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @else
                                                <input class="lesson-test-text-input" type="text"
                                                       name="answers[{{ $test->id }}]" placeholder="Ваша відповідь...">
                                            @endif
                                        </fieldset>
                                    @endforeach
                                </div>

                                <button type="submit" class="lesson-submit-button">
                                    <i class="bi bi-check2-circle"></i>
                                    {{ $lastTestAttempt ? 'Пройти ще раз' : 'Завершити тест' }}
                                </button>
                            </form>
                        </section>
                    @endif

                    @if($lesson->homework_text || $lesson->homework_video_url || !empty($lesson->homework_files))
                        <section class="lesson-system-block lesson-system-block--homework">
                            <div class="lesson-system-heading">
                                <span class="lesson-system-icon"><i class="bi bi-pencil-square"></i></span>
                                <div>
                                    <div class="lesson-system-kicker">Після уроку</div>
                                    <h2>Домашнє завдання</h2>
                                </div>
                            </div>

                            @if($lesson->homework_text)
                                <div class="lesson-prose">{!! $lesson->homework_text !!}</div>
                            @endif

                            @if($lesson->homework_video_url)
                                <div class="lesson-video-frame ratio ratio-16x9 mt-3">
                                    <iframe src="{{ $lesson->homework_video_url }}" title="Відео до домашнього завдання" allowfullscreen></iframe>
                                </div>
                            @endif

                            @if(!empty($lesson->homework_files))
                                <div class="lesson-files-list mt-3">
                                    @foreach($lesson->homework_files as $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" rel="noopener" class="lesson-file-item">
                                            <i class="bi bi-paperclip"></i>
                                            <span>{{ basename($file) }}</span>
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif
                </main>

                <aside class="lesson-sidebar">
                    <div class="lesson-sidebar-inner">
                        <div class="lesson-sidebar-title">Уроки курсу</div>
                        <nav class="lesson-nav">
                            @foreach($course->lessons as $courseLesson)
                                <a href="{{ route('courses.lessons.show', [$course, $courseLesson]) }}"
                                   class="lesson-nav-item {{ $courseLesson->id === $lesson->id ? 'lesson-nav-item--active' : '' }}">
                                    <span class="lesson-nav-num">{{ $courseLesson->position }}</span>
                                    <span class="lesson-nav-name">{{ $courseLesson->title }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            </div>
        </div>
    </div>
@endsection
