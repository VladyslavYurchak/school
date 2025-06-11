@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card">
            <div class="card-header">
                <h3>{{ $course->title }}</h3>
            </div>
            <div class="card-body">
                <p><strong>Опис:</strong> {{ $course->description }}</p>
                <p><strong>Мова:</strong> {{ $course->language->name }}</p>
                <p><strong>Ціна:</strong> {{ $course->price > 0 ? $course->price . ' грн' : 'Безкоштовний' }}</p>
                <p><strong>Статус:</strong> {{ $course->is_published ? 'Опублікований' : 'Неопублікований' }}</p>

                <a href="{{ route('admin.course.lesson.create', $course->id) }}" class="btn btn-primary btn-sm mb-3">Додати урок</a>

                <table class="table table-bordered mt-3">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Назва уроку</th>
                        <th>Тип уроку</th>
                        <th>Дії</th>
                    </tr>
                    </thead>
                    <tbody id="sortable-lessons">
                    @foreach ($course->lessons as $lesson)
                        <tr id="lesson-{{ $lesson->id }}" data-id="{{ $lesson->id }}">
                            <td class="sortable-handle">{{ $lesson->position }}</td>
                            <td>{{ $lesson->title }}</td>
                            <td>{{ $lesson->lesson_type }}</td>
                            <td>
                                <a href="{{ route('admin.course.lesson.show', $lesson->id) }}" class="btn btn-info btn-sm me-2">Переглянути</a>
                                <a href="{{ route('admin.course.lesson.test.create', $lesson->id) }}" class="btn btn-success btn-sm">+ Тестовий блок</a>
                                <form action="{{ route('admin.course.lesson.delete', $lesson->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Видалити</button>
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
        .table th, .table td { text-align: center; vertical-align: middle; }
        .sortable-handle { cursor: grab; }
        .btn { margin-bottom: 5px; }
    </style>
@endsection
