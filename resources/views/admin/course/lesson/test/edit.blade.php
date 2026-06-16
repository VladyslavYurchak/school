@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card shadow-lg border-0">
            <div class="card-header bg-white d-flex align-items-center">
                <h3 class="fw-bold text-dark mb-0">Редагувати тест</h3>
                <a href="{{ route('admin.course.lesson.test.create', $lesson->id) }}" class="btn btn-outline-secondary btn-sm ms-auto">
                    Назад
                </a>
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

                    <div class="mb-3">
                        <label for="question" class="form-label fw-bold">Питання</label>
                        <textarea name="question" id="question" class="form-control shadow-sm" rows="3">{{ old('question', $test->question) }}</textarea>
                        @if ($errors->has('question'))
                            <div class="alert alert-danger mt-2 shadow-sm rounded">
                                {{ $errors->first('question') }}
                            </div>
                        @endif
                    </div>

                    <h5 class="mb-1 fw-bold">Варіанти відповідей</h5>
                    <div class="form-text mb-3">Мінімум 3 відповіді, максимум 5. Позначте одну або кілька правильних.</div>

                    @php
                        $oldExistingOptions = old('options.existing');
                    @endphp

                    <div id="options-list">
                        @foreach($test->options as $option)
                            @php
                                $isChecked = is_array($oldExistingOptions)
                                    ? old("options.existing.$option->id.is_correct")
                                    : $option->is_correct;
                            @endphp
                            <div class="option-item mb-2 p-2 bg-light rounded shadow-sm d-flex align-items-center gap-2" data-id="{{ $option->id }}">
                                <input type="text"
                                       name="options[existing][{{ $option->id }}][option_text]"
                                       class="form-control w-50 shadow-sm"
                                       value="{{ old("options.existing.$option->id.option_text", $option->option_text) }}"
                                       maxlength="1000">
                                <div class="form-check ms-2">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           name="options[existing][{{ $option->id }}][is_correct]"
                                           value="1" {{ $isChecked ? 'checked' : '' }}>
                                    <label class="form-check-label">Правильна</label>
                                </div>
                                <button type="button"
                                        class="btn btn-danger btn-sm shadow-sm remove-option"
                                        data-id="{{ $option->id }}">
                                    Видалити
                                </button>
                            </div>
                        @endforeach

                        @foreach(old('options.new', []) as $index => $option)
                            <div class="option-item mb-2 p-2 bg-light rounded shadow-sm d-flex align-items-center gap-2" data-id="">
                                <input type="text"
                                       name="options[new][{{ $index }}][option_text]"
                                       class="form-control w-50 shadow-sm"
                                       value="{{ $option['option_text'] ?? '' }}"
                                       maxlength="1000">
                                <div class="form-check ms-2">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           name="options[new][{{ $index }}][is_correct]"
                                           value="1" {{ !empty($option['is_correct']) ? 'checked' : '' }}>
                                    <label class="form-check-label">Правильна</label>
                                </div>
                                <button type="button"
                                        class="btn btn-danger btn-sm shadow-sm remove-option"
                                        data-id="">
                                    Видалити
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="my-3">
                        <button type="button" class="btn btn-success shadow-sm" id="add-option">
                            Додати відповідь
                        </button>
                    </div>

                    @if ($errors->has('options'))
                        <div class="alert alert-danger shadow-sm rounded">
                            @foreach($errors->get('options') as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary shadow-sm">
                        Зберегти
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const optionsList = document.getElementById('options-list');
            const addOptionButton = document.getElementById('add-option');
            const deleteUrlTemplate = "{{ route('admin.course.lesson.test.option.destroy', ['option' => '__ID__']) }}";

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
                if (!event.target.classList.contains('remove-option')) {
                    return;
                }

                if (optionItems().length <= 3) {
                    alert('У тесті має бути щонайменше 3 відповіді.');
                    updateControls();
                    return;
                }

                const optionId = event.target.dataset.id;
                const optionItem = event.target.closest('.option-item');

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
                    <div class="option-item mb-2 p-2 bg-light rounded shadow-sm d-flex align-items-center gap-2" data-id="">
                        <input type="text"
                               name="options[new][${index}][option_text]"
                               class="form-control w-50 shadow-sm"
                               placeholder="Нова відповідь"
                               maxlength="1000">
                        <div class="form-check ms-2">
                            <input type="checkbox"
                                   class="form-check-input"
                                   name="options[new][${index}][is_correct]"
                                   value="1">
                            <label class="form-check-label">Правильна</label>
                        </div>
                        <button type="button"
                                class="btn btn-danger btn-sm shadow-sm remove-option"
                                data-id="">
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
