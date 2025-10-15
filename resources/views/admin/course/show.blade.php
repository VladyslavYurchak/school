@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center bg-light border-bottom">
                <h3 class="mb-0 text-dark">{{ $course->title }}</h3>
                <a href="{{ route('admin.course.index') }}" class="btn btn-outline-dark btn-sm ms-auto">
                    ← Назад
                </a>
            </div>

            <div class="card-body">
                <p><strong>Опис:</strong> {{ $course->description }}</p>
                <p><strong>Мова:</strong> {{ $course->language->name }}</p>

                <a href="{{ route('admin.course.lesson.create', $course->id) }}" class="btn btn-dark btn-sm mb-3">
                    + Додати урок
                </a>

                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-light">
                    <tr>
                        <th style="width:5%">#</th>
                        <th style="width:20%">Назва уроку</th>
                        <th style="width:30%">Опис</th>
                        <th style="width:15%">Тип уроку</th>
                        <th style="width:30%">Дії</th>
                    </tr>
                    </thead>
                    <tbody id="sortable-lessons">
                    @foreach ($course->lessons as $lesson)
                        <tr id="lesson-{{ $lesson->id }}" data-id="{{ $lesson->id }}">
                            <td class="sortable-handle text-muted">{{ $lesson->position }}</td>
                            <td class="text-start">{{ $lesson->title }}</td>
                            <td class="text-start small text-secondary">{{ $lesson->description }}</td>
                            <td class="text-start">{{ $lesson->lesson_type }}</td>
                            <td class="text-center">
                                <a href="{{ route('admin.course.lesson.show', $lesson->id) }}"
                                   class="btn btn-outline-dark btn-sm me-1">👁️</a>

                                {{-- Основна частина --}}
                                <a href="{{ route('admin.course.lesson.main.create', $lesson->id) }}"
                                   class="btn {{ !empty($lesson->content) ? 'btn-outline-secondary' : 'btn-outline-success' }} btn-sm me-1">
                                    {{ !empty($lesson->content) ? 'Ред. осн.част.' : 'Дод. осн. част.' }}
                                </a>

                                {{-- Тестовий блок --}}
                                <a href="{{ route('admin.course.lesson.test.create', $lesson->id) }}"
                                   class="btn {{ $lesson->tests()->exists() ? 'btn-outline-secondary' : 'btn-outline-success' }} btn-sm me-1">
                                    {{ $lesson->tests()->exists() ? 'Ред. тест' : 'Дод. тест' }}
                                </a>

                                {{-- Домашнє завдання --}}
                                @php
                                    $hasHomework = !empty($lesson->homework_text) || !empty($lesson->homework_video_url) || !empty(json_decode($lesson->homework_files, true));
                                @endphp
                                <a href="{{ $hasHomework ? route('admin.course.lesson.homework.edit', $lesson->id) : route('admin.course.lesson.homework.create', $lesson->id) }}"
                                   class="btn {{ $hasHomework ? 'btn-outline-warning' : 'btn-outline-success' }} btn-sm me-1">
                                    {{ $hasHomework ? 'Ред. дом.завд.' : 'Дод. дом.завд.' }}
                                </a>

                                <form action="{{ route('admin.course.lesson.delete', $lesson->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sortable = new Sortable(document.getElementById('sortable-lessons'), {
                handle: '.sortable-handle',
                animation: 150,
                onEnd(evt) {
                    const newOrder = [];
                    document.querySelectorAll('#sortable-lessons tr').forEach((row, index) => {
                        newOrder.push({
                            id: row.dataset.id,
                            position: index + 1
                        });
                    });
                    fetch('{{ route('admin.course.lesson.updateOrder') }}', {
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

    <style>
        .sortable-handle { cursor: grab; }
        .btn { border-radius: 6px; }
        .table td, .table th { text-align: center; }
        .table td:nth-child(3) {
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
        }
    </style>
@endsection
