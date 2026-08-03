@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-mortarboard"></i>
                            Курс
                        </span>
                        <h1 class="admin-title">{{ $course->title }}</h1>
                        <p class="admin-subtitle">
                            {{ $course->description ?: 'Опис курсу ще не додано.' }}
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.create', $course->id) }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Додати урок
                        </a>
                        <a href="{{ route('admin.course.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До курсів
                        </a>
                    </div>
                </div>
            </section>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані курсу</h2>
                    <span class="admin-badge admin-badge-muted">{{ $course->language->name }}</span>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-content-box">
                        <strong>Мова:</strong> {{ $course->language->name }}<br>
                        <strong>Ціна:</strong>
                        @if($course->price > 0)
                            {{ $course->price }} грн
                        @else
                            Безкоштовний
                        @endif
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Уроки курсу</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $course->lessons->count() }}</span>
                </div>

                <div class="admin-panel-body p-0">
                    @if($course->lessons->count())
                        <div class="admin-table-wrap border-0 rounded-0">
                            <table class="table admin-table mb-0 admin-lessons-table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Назва уроку</th>
                                    <th>Опис</th>
                                    <th>Тип</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                                </thead>
                                <tbody id="sortable-lessons">
                                @foreach ($course->lessons as $lesson)
                                    <tr id="lesson-{{ $lesson->id }}" data-id="{{ $lesson->id }}">
                                        <td class="sortable-handle text-muted">{{ $lesson->position }}</td>
                                        <td>
                                            <a href="{{ route('admin.course.lesson.show', $lesson->id) }}" class="admin-course-link">
                                                {{ $lesson->title }}
                                            </a>
                                            @unless($lesson->is_published)
                                                <span class="admin-badge admin-badge-muted ms-1">Чернетка</span>
                                            @endunless
                                        </td>
                                        <td class="small text-muted">{{ $lesson->description ?: '—' }}</td>
                                        <td>{{ $lesson->lesson_type }}</td>
                                        <td class="text-end">
                                            @php
                                                $homeworkFiles = is_array($lesson->homework_files)
                                                    ? $lesson->homework_files
                                                    : (json_decode($lesson->homework_files ?? '[]', true) ?: []);

                                                $hasHomework = !empty($lesson->homework_text)
                                                    || !empty($lesson->homework_video_url)
                                                    || !empty($homeworkFiles);
                                            @endphp

                                            <div class="admin-row-actions">
                                                <a href="{{ route('admin.course.lesson.show', $lesson->id) }}" class="btn btn-sm btn-outline-secondary">Перегляд</a>
                                                <a href="{{ route('admin.course.lesson.edit', $lesson->id) }}" class="btn btn-sm btn-outline-primary">Редагувати</a>
                                                <a href="{{ route('admin.course.lesson.blocks.index', $lesson->id) }}"
                                                   class="btn btn-sm {{ $lesson->content_blocks_count > 0 ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                    {{ $lesson->content_blocks_count > 0 ? 'Конструктор (' . $lesson->content_blocks_count . ')' : 'Додати матеріали' }}
                                                </a>
                                                <a href="{{ route('admin.course.lesson.test.create', $lesson->id) }}"
                                                   class="btn btn-sm {{ $lesson->tests_count > 0 ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                    {{ $lesson->tests_count > 0 ? 'Тест' : 'Додати тест' }}
                                                </a>
                                                <a href="{{ route('admin.course.lesson.vocabulary.index', $lesson->id) }}"
                                                   class="btn btn-sm {{ $lesson->vocabulary_items_count > 0 ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                    {{ $lesson->vocabulary_items_count > 0 ? 'Словник (' . $lesson->vocabulary_items_count . ')' : 'Додати слова' }}
                                                </a>
                                                <a href="{{ route('admin.course.lesson.exercises.index', $lesson->id) }}"
                                                   class="btn btn-sm {{ $lesson->exercises_count > 0 ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                    {{ $lesson->exercises_count > 0 ? 'Вправи (' . $lesson->exercises_count . ')' : 'Додати вправу' }}
                                                </a>
                                                <a href="{{ $hasHomework ? route('admin.course.lesson.homework.edit', $lesson->id) : route('admin.course.lesson.homework.create', $lesson->id) }}"
                                                   class="btn btn-sm {{ $hasHomework ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                    {{ $hasHomework ? 'Домашнє' : 'Додати домашнє' }}
                                                </a>
                                                @if($lesson->hasPurchaseHistory())
                                                    <span class="admin-badge admin-badge-muted" title="Зніміть урок з публікації замість видалення">
                                                        Є оплати
                                                    </span>
                                                @else
                                                    <form action="{{ route('admin.course.lesson.delete', $lesson->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Ви впевнені?')">
                                                            Видалити
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <i class="bi bi-journal-plus"></i>
                            <h3>Уроків поки немає</h3>
                            <p>Додайте перший урок, щоб наповнити курс матеріалами.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const lessonsList = document.getElementById('sortable-lessons');

            if (!lessonsList) {
                return;
            }

            new Sortable(lessonsList, {
                handle: '.sortable-handle',
                animation: 150,
                onEnd() {
                    const newOrder = [];
                    document.querySelectorAll('#sortable-lessons tr').forEach((row, index) => {
                        newOrder.push({
                            id: row.dataset.id,
                            position: index + 1
                        });
                    });
                    fetch('{{ route('admin.course.lesson.updateOrder', $course) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ lessons: newOrder })
                    });
                }
            });
        });
    </script>
@endsection
