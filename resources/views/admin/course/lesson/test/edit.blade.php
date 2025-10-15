@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-white d-flex align-items-center">
                <h3 class="fw-bold text-dark mb-0">Редагувати тест</h3>
                <a href="{{ route('admin.course.lesson.test.create', $lesson->id) }}" class="btn btn-outline-secondary btn-sm ms-auto">
                    ← Назад
                </a>
            </div>

        </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.course.lesson.test.update', [$lesson->id, $test->id]) }}">
                    @csrf
                    @method('PATCH')

                    <!-- Редагувати питання до тесту -->
                    <div class="mb-3">
                        <label for="question" class="form-label fw-bold">Редагувати питання до тесту</label>
                        <textarea name="question" id="question" class="form-control shadow-sm" rows="3">{{ old('question', $test->question) }}</textarea>
                        @if ($errors->has('question'))
                            <div class="alert alert-danger mt-2 shadow-sm rounded">
                                {{ $errors->first('question') }}
                            </div>
                        @endif
                    </div>

                    <!-- Редагувати варіанти відповідей -->
                    <h5 class="mb-3 fw-bold">Редагувати варіанти відповідей</h5>
                    <div id="options-list">
                        @foreach($test->options as $option)
                            <div class="option-item mb-2 p-2 bg-light rounded shadow-sm d-flex align-items-center gap-2" data-id="{{ $option->id }}">
                                <input type="text" name="options[existing][{{ $option->id }}][option_text]"
                                       class="form-control w-50 shadow-sm"
                                       value="{{ $option->option_text }}">
                                <div class="form-check ms-2">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           name="options[existing][{{ $option->id }}][is_correct]"
                                           value="1" {{ $option->is_correct ? 'checked' : '' }}>
                                    <label class="form-check-label">Правильна</label>
                                </div>
                                <button type="button"
                                        class="btn btn-danger btn-sm shadow-sm remove-option"
                                        data-id="{{ $option->id }}">
                                    🗑️
                                </button>
                            </div>
                        @endforeach
                    </div>


                    <div class="my-3">
                        <button type="button" class="btn btn-success shadow-sm" id="add-option">
                            ➕ Додати варіант
                        </button>
                    </div>

                    @if ($errors->has('options'))
                        <div class="alert alert-danger shadow-sm rounded">
                            {{ $errors->first('options') }}
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary shadow-sm">
                        💾 Зберегти зміни
                    </button>
                </form>
            </div>

        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            console.log("Скрипт редагування тесту завантажено");
            const optionsList = document.getElementById('options-list');

            // Функція для блокування кнопок видалення, якщо залишилося менше 3-х варіантів
            function updateDeleteButtons() {
                const options = optionsList.querySelectorAll('.option-item');
                options.forEach(optionItem => {
                    const removeBtn = optionItem.querySelector('.remove-option');
                    removeBtn.disabled = (options.length <= 3);
                });
            }

            // Видалення опції: якщо існує data-id, відправляємо AJAX-запит; для нових – видаляємо елемент
            optionsList.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-option')) {
                    const optionId = e.target.dataset.id;
                    const optionItem = e.target.closest('.option-item');

                    if (!optionId) {
                        optionItem.remove();
                        updateDeleteButtons();
                        return;
                    }

                    fetch(`/admin/course/lesson/test/option/${optionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                optionItem.remove();
                                updateDeleteButtons();
                            } else {
                                alert(data.message);
                            }
                        })
                        .catch(err => console.error('Ajax error', err));
                }
            });

            // Додавання нової опції
            document.getElementById('add-option').addEventListener('click', function () {
                const currentOptions = optionsList.querySelectorAll('.option-item');
                if (currentOptions.length >= 5) {
                    alert('Можна додати максимум 5 варіантів відповіді.');
                    return;
                }

                const index = Date.now(); // Використовуємо унікальний індекс для нової опції
                const html = `
    <div class="option-item mb-2 p-2 bg-light rounded shadow-sm d-flex align-items-center gap-2">
        <input type="text"
               name="options[new][${index}][option_text]"
               class="form-control w-50 shadow-sm"
               placeholder="Новий варіант">

        <div class="form-check ms-2">
            <input type="checkbox"
                   class="form-check-input"
                   name="options[new][${index}][is_correct]">
            <label class="form-check-label">Правильна</label>
        </div>

        <button type="button"
                class="btn btn-danger btn-sm shadow-sm remove-option"
                data-id="">
            🗑️
        </button>
    </div>`;

                optionsList.insertAdjacentHTML('beforeend', html);
                updateDeleteButtons();
            });

            updateDeleteButtons();
        });
    </script>
@endsection
