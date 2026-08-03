@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page test-block-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-ui-checks"></i>
                            Тестовий блок
                        </span>
                        <h1 class="admin-title">Редагувати тест</h1>
                        <p class="admin-subtitle">{{ $lesson->title }}</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.test.create', $lesson->id) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До тестів
                        </a>
                        <a href="{{ route('admin.course.lesson.edit', $lesson->id) }}" class="admin-btn-soft">
                            <i class="bi bi-pencil"></i>
                            Редагувати урок
                        </a>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.course.lesson.test.update', [$lesson->id, $test->id]) }}" class="admin-panel admin-form">
                @csrf
                @method('PATCH')

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Питання</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="question" class="admin-form-label">Питання</label>
                        <textarea name="question" id="question" class="form-control question-textarea" rows="3">{{ old('question', $test->question) }}</textarea>
                        @if ($errors->has('question'))
                            <div class="alert alert-danger mt-2">{{ $errors->first('question') }}</div>
                        @endif
                    </div>

                    <div class="admin-form-section">
                        <label class="admin-form-label">Варіанти відповідей</label>
                        <div class="form-text mb-3">Мінімум 3 відповіді, максимум 5. Позначте одну або кілька правильних.</div>

                        @php
                            $oldExistingOptions = old('options.existing');
                        @endphp

                        <div id="options-list" class="options-container">
                            @foreach($test->options as $option)
                                @php
                                    $isChecked = is_array($oldExistingOptions)
                                        ? old("options.existing.$option->id.is_correct")
                                        : $option->is_correct;
                                @endphp
                                <div class="option-item" data-id="{{ $option->id }}">
                                    <input type="text"
                                           name="options[existing][{{ $option->id }}][option_text]"
                                           class="form-control option-input"
                                           value="{{ old("options.existing.$option->id.option_text", $option->option_text) }}"
                                           maxlength="1000">
                                    <label class="custom-checkbox">
                                        <input type="checkbox"
                                               name="options[existing][{{ $option->id }}][is_correct]"
                                               value="1" {{ $isChecked ? 'checked' : '' }}>
                                        <span class="checkmark"></span>
                                        Правильна
                                    </label>
                                    <button type="button" class="admin-btn-danger remove-option" data-id="{{ $option->id }}">
                                        <i class="bi bi-trash"></i>
                                        Видалити
                                    </button>
                                </div>
                            @endforeach

                            @foreach(old('options.new', []) as $index => $option)
                                <div class="option-item" data-id="">
                                    <input type="text"
                                           name="options[new][{{ $index }}][option_text]"
                                           class="form-control option-input"
                                           value="{{ $option['option_text'] ?? '' }}"
                                           maxlength="1000">
                                    <label class="custom-checkbox">
                                        <input type="checkbox"
                                               name="options[new][{{ $index }}][is_correct]"
                                               value="1" {{ !empty($option['is_correct']) ? 'checked' : '' }}>
                                        <span class="checkmark"></span>
                                        Правильна
                                    </label>
                                    <button type="button" class="admin-btn-danger remove-option" data-id="">
                                        <i class="bi bi-trash"></i>
                                        Видалити
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($errors->has('options'))
                        <div class="alert alert-danger">
                            @foreach($errors->get('options') as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="admin-form-actions">
                        <button type="button" class="admin-btn-soft" id="add-option">
                            <i class="bi bi-plus-lg"></i>
                            Додати відповідь
                        </button>
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Зберегти
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const optionsList = document.getElementById('options-list');
            const addOptionButton = document.getElementById('add-option');
            const deleteUrlTemplate = "{{ route('admin.course.lesson.test.option.destroy', [
                'lesson' => $lesson,
                'test' => $test,
                'option' => '__ID__',
            ]) }}";

            function optionItems() {
                return Array.from(optionsList.querySelectorAll('.option-item'));
            }

            function updateControls() {
                const count = optionItems().length;

                optionItems().forEach(optionItem => {
                    const removeButton = optionItem.querySelector('.remove-option');
                    removeButton.disabled = count <= 3;
                });

                addOptionButton.disabled = count >= 5;
            }

            optionsList.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.remove-option');
                if (!removeButton) {
                    return;
                }

                if (optionItems().length <= 3) {
                    alert('У тесті має бути щонайменше 3 відповіді.');
                    updateControls();
                    return;
                }

                const optionId = removeButton.dataset.id;
                const optionItem = removeButton.closest('.option-item');

                if (!optionId) {
                    optionItem.remove();
                    updateControls();
                    return;
                }

                fetch(deleteUrlTemplate.replace('__ID__', optionId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            optionItem.remove();
                            updateControls();
                            return;
                        }

                        alert(data.message);
                    })
                    .catch(error => console.error('Ajax error', error));
            });

            addOptionButton.addEventListener('click', function () {
                if (optionItems().length >= 5) {
                    alert('У тесті може бути не більше 5 відповідей.');
                    updateControls();
                    return;
                }

                const index = Date.now();
                const html = `
                    <div class="option-item" data-id="">
                        <input type="text"
                               name="options[new][${index}][option_text]"
                               class="form-control option-input"
                               placeholder="Нова відповідь"
                               maxlength="1000">
                        <label class="custom-checkbox">
                            <input type="checkbox"
                                   name="options[new][${index}][is_correct]"
                                   value="1">
                            <span class="checkmark"></span>
                            Правильна
                        </label>
                        <button type="button" class="admin-btn-danger remove-option" data-id="">
                            <i class="bi bi-trash"></i>
                            Видалити
                        </button>
                    </div>`;

                optionsList.insertAdjacentHTML('beforeend', html);
                updateControls();
            });

            updateControls();
        });
    </script>
@endsection
