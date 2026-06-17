@extends('public.layouts.main')

@section('content')

    @if(!$lesson->isAvailableFor(auth()->user()))
        {{-- ЗАГЛУШКА --}}
        <div class="lesson-locked-wrap">
            <div class="lesson-locked-card">
                <div class="lesson-locked-icon">🔒</div>
                <h2 class="lesson-locked-title">Контент закрито</h2>
                <p class="lesson-locked-sub">{{ $lesson->title }}</p>
                @if($lesson->isPaid())
                    <form action="{{ route('lessons.buy', $lesson) }}" method="POST">
                        @csrf
                        <button type="submit" class="lesson-buy-btn">
                            Придбати урок — {{ number_format($lesson->price, 0, ',', ' ') }} грн
                        </button>
                    </form>
                    <div class="lesson-locked-or">або</div>
                @endif
                <a href="{{ route('courses.show', $course) }}" class="lesson-course-link">
                    ← Переглянути курс
                </a>
            </div>
        </div>

    @else
        {{-- КОНТЕНТ УРОКУ --}}
        <div class="lesson-page">

            {{-- Хедер уроку --}}
            <div class="lesson-header">
                <div class="container">
                    <a href="{{ route('courses.show', $course) }}" class="lesson-back">
                        ← {{ $course->title }}
                    </a>
                    <h1 class="lesson-title">{{ $lesson->title }}</h1>
                </div>
            </div>

            <div class="container">
                <div class="lesson-layout">

                    {{-- Основний контент --}}
                    <div class="lesson-main">

                        {{-- Відео --}}
                        @if($lesson->video_url)
                            <div class="lesson-block lesson-block--video">
                                <div class="ratio ratio-16x9">
                                    <iframe
                                        src="{{ $lesson->video_url }}"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                    ></iframe>                                </div>
                            </div>
                        @endif

                        {{-- Аудіо --}}
                        @if($lesson->audio_file)
                            <div class="lesson-block lesson-block--audio">
                                <div class="lesson-block-label">🎧 Аудіо</div>
                                <audio controls src="{{ asset('storage/' . $lesson->audio_file) }}" class="w-100"></audio>
                            </div>
                        @endif

                        {{-- Текстовий контент --}}
                        @if($lesson->content)
                            <div class="lesson-content">
                                <h4>📖 Матеріал уроку</h4>
                                <div class="lesson-prose">
                                    {!! $lesson->content !!}
                                </div>
                            </div>
                        @endif

                        {{-- Медіафайли --}}
                        @if(!empty($lesson->media_files))
                            <div class="lesson-block lesson-block--files">
                                <div class="lesson-block-label">📎 Матеріали для завантаження</div>
                                <div class="lesson-files-list">
                                    @foreach($lesson->media_files as $file)
                                        <a href="{{ asset('storage/' . $file) }}" target="_blank" class="lesson-file-item">
                                            <span class="lesson-file-icon">📄</span>
                                            <span class="lesson-file-name">{{ basename($file) }}</span>
                                            <span class="lesson-file-dl">↓</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Домашнє завдання --}}
                        @if($lesson->homework_text || $lesson->homework_video_url || !empty($lesson->homework_files))
                            <div class="lesson-block lesson-block--homework">
                                <div class="lesson-block-label">✏️ Домашнє завдання</div>

                                @if($lesson->homework_text)
                                    <div class="lesson-text">
                                        {!! $lesson->homework_text !!}
                                    </div>
                                @endif

                                @if($lesson->homework_video_url)
                                    <div class="lesson-homework-video mt-3">
                                        <div class="lesson-block-label">🎬 Відео до домашнього завдання</div>

                                        <div class="ratio ratio-16x9">
                                            <iframe
                                                src="{{ $lesson->homework_video_url }}"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                                allowfullscreen
                                            ></iframe>
                                        </div>
                                    </div>
                                @endif

                                @if(!empty($lesson->homework_files))
                                    <div class="lesson-files-list mt-3">
                                        @foreach($lesson->homework_files as $file)
                                            <a href="{{ asset('storage/' . $file) }}" target="_blank" class="lesson-file-item">
                                                <span class="lesson-file-icon">📄</span>
                                                <span class="lesson-file-name">{{ basename($file) }}</span>
                                                <span class="lesson-file-dl">↓</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Тести --}}
                        @if($lesson->tests->count())
                            <div class="lesson-block lesson-block--tests">
                                <div class="lesson-block-label">🧠 Тест</div>

                                @if($lastTestAttempt)
                                    <div class="alert alert-info mb-4">
                                        <strong>Ваш останній результат:</strong><br>

                                        {{ $lastTestAttempt->score }} / {{ $lastTestAttempt->max_score }}
                                        — {{ $lastTestAttempt->percent }}%

                                        @if($lastTestAttempt->passed)
                                            <div class="mt-1 text-success">Тест пройдено ✅</div>
                                        @else
                                            <div class="mt-1 text-danger">Тест не пройдено ❌</div>
                                        @endif

                                        <div class="small text-muted mt-1">
                                            Дата: {{ $lastTestAttempt->finished_at?->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                @endif

                                <form action="{{ route('courses.lessons.tests.submit', [$course, $lesson]) }}" method="POST">
                                    @csrf

                                    <div class="lesson-tests">
                                        @foreach($lesson->tests as $i => $test)
                                            <div class="lesson-test-item">
                                                <div class="lesson-test-question">
                                                    <span class="lesson-test-num">{{ $i + 1 }}</span>
                                                    {{ $test->question }}
                                                </div>

                                                @if($test->options->count())
                                                    <div class="lesson-test-options">
                                                        @foreach($test->options as $option)
                                                            <label class="lesson-test-option">
                                                                <input
                                                                    type="{{ $test->is_multiple_choice ? 'checkbox' : 'radio' }}"
                                                                    name="answers[{{ $test->id }}]{{ $test->is_multiple_choice ? '[]' : '' }}"
                                                                    value="{{ $option->id }}"
                                                                >
                                                                <span>{{ $option->option_text }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <input
                                                        class="lesson-test-text-input"
                                                        type="text"
                                                        name="answers[{{ $test->id }}]"
                                                        placeholder="Ваша відповідь..."
                                                    >
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <button type="submit" class="btn btn-success mt-3">
                                        {{ $lastTestAttempt ? 'Пройти ще раз' : 'Завершити тест' }}
                                    </button>
                                </form>
                            </div>
                        @endif

                    </div>{{-- /lesson-main --}}

                    {{-- Сайдбар: список уроків --}}
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

                </div>{{-- /lesson-layout --}}
            </div>{{-- /container --}}
        </div>{{-- /lesson-page --}}
    @endif

    <style>
        /* ===================== LOCKED ===================== */
        .lesson-locked-wrap {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .lesson-locked-card {
            text-align: center;
            background: #fff;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            box-shadow: 0 4px 32px rgba(0,0,0,.08);
            max-width: 400px;
            width: 100%;
        }

        .lesson-locked-icon { font-size: 3rem; margin-bottom: 1rem; }
        .lesson-locked-title { font-size: 1.5rem; font-weight: 700; margin-bottom: .5rem; }
        .lesson-locked-sub { color: #6c757d; margin-bottom: 1.5rem; }
        .lesson-buy-btn {
            display: inline-block;
            background: #0d6efd;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: .75rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
            width: 100%;
        }
        .lesson-buy-btn:hover { background: #0b5ed7; }
        .lesson-locked-or { color: #adb5bd; margin: 1rem 0; font-size: .9rem; }
        .lesson-course-link { color: #0d6efd; text-decoration: none; font-size: .95rem; }
        .lesson-course-link:hover { text-decoration: underline; }

        /* ===================== PAGE ===================== */
        .lesson-page { padding-bottom: 4rem; }

        .lesson-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem 0 1.25rem;
            margin-bottom: 2rem;
        }
        .lesson-back {
            display: inline-block;
            color: #6c757d;
            text-decoration: none;
            font-size: .9rem;
            margin-bottom: .5rem;
            transition: color .2s;
        }
        .lesson-back:hover { color: #0d6efd; }
        .lesson-title {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 700;
            margin: 0;
            line-height: 1.3;
        }

        /* ===================== LAYOUT ===================== */
        .lesson-layout {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 2rem;
            align-items: start;
        }
        @media (max-width: 991px) {
            .lesson-layout { grid-template-columns: 1fr; }
            .lesson-sidebar { order: -1; }
        }

        /* ===================== BLOCKS ===================== */
        .lesson-block {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }
        .lesson-block--homework {
            border-left: 4px solid #fd7e14;
            background: #fffbf7;
        }
        .lesson-block--tests {
            border-left: 4px solid #198754;
            background: #f8fffe;
        }
        .lesson-block--audio {
            border-left: 4px solid #6f42c1;
            background: #fdf8ff;
        }
        .lesson-block--files {
            border-left: 4px solid #0dcaf0;
            background: #f0fdff;
        }
        .lesson-block--video { border: none; padding: 0; background: transparent; }

        .lesson-block-label {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6c757d;
            margin-bottom: 1rem;
        }

        /* ===================== PROSE ===================== */
        .lesson-prose {
            font-size: 1rem;
            line-height: 1.8;
            color: #212529;
            white-space: pre-wrap;   /* зберігає пробіли та переноси рядків */
        }

        /* ===================== FILES ===================== */
        .lesson-files-list { display: flex; flex-direction: column; gap: .5rem; }
        .lesson-file-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .6rem .9rem;
            background: #f8f9fa;
            border-radius: 8px;
            text-decoration: none;
            color: #212529;
            border: 1px solid #e9ecef;
            transition: background .15s, border-color .15s;
            font-size: .9rem;
        }
        .lesson-file-item:hover { background: #e9ecef; border-color: #ced4da; color: #212529; }
        .lesson-file-icon { font-size: 1.1rem; }
        .lesson-file-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .lesson-file-dl { color: #6c757d; font-size: .85rem; }

        .lesson-hw-video-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            color: #fd7e14;
            text-decoration: none;
            font-weight: 500;
            margin-top: .75rem;
        }
        .lesson-hw-video-link:hover { text-decoration: underline; }

        /* ===================== TESTS ===================== */
        .lesson-tests { display: flex; flex-direction: column; gap: 1.25rem; }
        .lesson-test-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
            border: 1px solid #e9ecef;
        }
        .lesson-test-question {
            font-weight: 600;
            margin-bottom: .85rem;
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            line-height: 1.5;
        }
        .lesson-test-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #198754;
            color: #fff;
            border-radius: 50%;
            min-width: 1.6rem;
            height: 1.6rem;
            font-size: .8rem;
            font-weight: 700;
            flex-shrink: 0;
            margin-top: .05rem;
        }
        .lesson-test-options { display: flex; flex-direction: column; gap: .5rem; }
        .lesson-test-option {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .45rem .75rem;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 7px;
            cursor: pointer;
            font-size: .95rem;
            transition: background .15s;
        }
        .lesson-test-option input { flex-shrink: 0; }
        .lesson-test-text-input {
            width: 100%;
            border: 1px solid #dee2e6;
            border-radius: 7px;
            padding: .45rem .75rem;
            font-size: .95rem;
            background: #fff;
            color: #6c757d;
        }

        /* ===================== SIDEBAR ===================== */
        .lesson-sidebar-inner {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            overflow: hidden;
            position: sticky;
            top: 1rem;
        }
        .lesson-sidebar-title {
            font-size: .75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6c757d;
            padding: 1rem 1.25rem .75rem;
            border-bottom: 1px solid #e9ecef;
        }
        .lesson-nav { display: flex; flex-direction: column; max-height: 70vh; overflow-y: auto; }
        .lesson-nav-item {
            display: flex;
            align-items: flex-start;
            gap: .75rem;
            padding: .75rem 1.25rem;
            text-decoration: none;
            color: #495057;
            font-size: .9rem;
            border-bottom: 1px solid #f1f3f5;
            transition: background .15s, color .15s;
            line-height: 1.4;
        }
        .lesson-nav-item:hover { background: #f8f9fa; color: #0d6efd; }
        .lesson-nav-item--active {
            background: #e7f1ff;
            color: #0d6efd;
            font-weight: 600;
        }
        .lesson-nav-item:last-child { border-bottom: none; }
        .lesson-nav-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            background: #e9ecef;
            border-radius: 4px;
            font-size: .75rem;
            font-weight: 700;
            flex-shrink: 0;
            color: #495057;
            margin-top: .05rem;
        }
        .lesson-nav-item--active .lesson-nav-num {
            background: #0d6efd;
            color: #fff;
        }
    </style>

@endsection
