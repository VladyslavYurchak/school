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

                <!-- Кнопки для редагування та видалення -->
                <div class="d-flex justify-content-start mb-3">
                    <a href="{{ route('admin.course.edit', $course->id) }}" class="btn btn-warning btn-sm me-2">Редагувати</a>
                    <form action="{{ route('admin.course.delete', $course->id) }}" method="POST" style="display:inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ви впевнені, що хочете видалити цей курс?');">Видалити</button>
                    </form>
                </div>

                <!-- Додати урок -->
                <a href="{{ route('admin.lesson.create', $course->id) }}" class="btn btn-success btn-sm mt-3">Додати урок</a>

                <!-- Таблиця уроків -->
                <table class="table table-striped mt-3 text-center"> <!-- Додано клас вирівнювання text-center -->
                    <thead>
                    <tr>
                        <th class="text-center">Порядковий номер</th> <!-- Заголовок стовпчика -->
                        <th>Назва уроку</th>
                        <th>Тип уроку</th>
                        <th>Дії</th>
                    </tr>
                    </thead>
                    <tbody id="sortable-lessons">
                    @foreach ($course->lessons as $lesson)

                        <tr id="lesson-{{ $lesson->id }}" data-id="{{ $lesson->id }}" onclick="location.href='{{ route('admin.lesson.show', $lesson->id) }}'" style="cursor: pointer;">
                            <td class="sortable-handle">
                                <div class="drag-icon" title="Тягни, щоб змінити порядок">
                                    <span class="lesson-position">{{ $lesson->position }}</span>
                                </div>
                            </td>
                            <td>{{ $lesson->title }}</td>
                            <td>{{ $lesson->lesson_type }}</td>
                            <td>
                                <form action="{{ route('admin.lesson.delete', $lesson->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Ви впевнені, що хочете видалити цей урок?');">
                                        Видалити
                                    </button>
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
                handle: '.sortable-handle', // Додаємо клас для ручки
                animation: 150, // Анімація під час переміщення
                onEnd(evt) {
                    const newOrder = [];
                    const rows = document.querySelectorAll('#sortable-lessons tr');

                    rows.forEach((row, index) => {
                        newOrder.push({
                            id: row.dataset.id,
                            position: index + 1 // Генеруємо нову позицію
                        });
                        row.querySelector('td:first-child div').textContent = index + 1; // Оновлюємо відображення позиції
                    });

                    // Надсилання даних на сервер
                    fetch('{{ route('admin.lesson.updateOrder') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ lessons: newOrder })
                    }).then(response => {
                        if (response.ok) {
                            console.log('Order updated successfully!');
                        } else {
                            alert('Не вдалося оновити порядок уроків. Спробуйте пізніше.');
                        }
                    });
                }
            });
        });
    </script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> {{-- Імпортуємо іконки --}}
    <style>
        /* Вирівнювання заголовка і даних */
        .table th, .table td {
            vertical-align: middle; /* Вертикальне вирівнювання центру в осередках таблиці */
            text-align: center; /* Горизонтальне вирівнювання */
        }

        /* Стиль для перетягуваної іконки */
        .drag-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 50%;
        }

        .drag-icon:hover {
            background-color: #e0e0e0; /* Hover ефект для більшої інтерактивності */
        }

        .material-icons {
            font-size: 18px;
            color: #6c757d; /* Сірий колір іконки */
        }
    </style>
@endsection
