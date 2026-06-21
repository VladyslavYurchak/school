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
                        <h1 class="admin-title">{{ $lesson->title }}</h1>
                        <p class="admin-subtitle">Питання тесту, варіанти відповідей і попередній перегляд.</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.edit', $lesson->id) }}" class="admin-btn-soft">
                            <i class="bi bi-pencil"></i>
                            Редагувати урок
                        </a>
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До курсу
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

            @if ($errors->has('options'))
                <div class="alert alert-danger">
                    @foreach($errors->get('options') as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('admin.course.lesson.test.store', $lesson->id) }}" class="admin-panel admin-form">
                @csrf

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Додати тестове питання</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="question" class="admin-form-label">Питання</label>
                        <textarea name="question" id="question" class="form-control question-textarea" rows="3">{{ old('question') }}</textarea>
                        @if ($errors->has('question'))
                            <div class="alert alert-danger mt-2">{{ $errors->first('question') }}</div>
                        @endif
                    </div>

                    @php
                        $oldOptions = old('options.new', []);
                        $defaultCount = min(5, max(3, count($oldOptions)));
                    @endphp

                    <div class="admin-form-section">
                        <label class="admin-form-label">Варіанти відповідей</label>
                        <div class="form-text mb-2">Мінімум 3 відповіді, максимум 5. Позначте одну або кілька правильних.</div>
                        <div class="options-container">
                            @for ($i = 0; $i < $defaultCount; $i++)
                                <div class="option-row" data-index="{{ $i }}">
                                    <input type="text"
                                           name="options[new][{{ $i }}][option_text]"
                                           class="form-control option-input"
                                           placeholder="Варіант відповіді"
                                           value="{{ old("options.new.$i.option_text") }}"
                                           maxlength="1000">
                                    <label class="custom-checkbox">
                                        <input type="checkbox"
                                               name="options[new][{{ $i }}][is_correct]"
                                               value="1"
                                            {{ old("options.new.$i.is_correct") ? 'checked' : '' }}>
                                        <span class="checkmark"></span>
                                        Правильна
                                    </label>
                                    <button type="button" class="admin-btn-danger remove-option" data-index="{{ $i }}">
                                        <i class="bi bi-trash"></i>
                                        Видалити
                                    </button>
                                </div>
                            @endfor
                        </div>
                    </div>

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

            @include('admin.course.lesson.test.list', compact('tests'))
            @include('admin.course.lesson.test.list-test', ['tests' => $tests, 'lesson' => $lesson])
        </div>
    </div>

    @push('scripts')
        <script>
            const updateOrderUrl = "{{ route('admin.course.lesson.test.updateOrder', ['lesson' => $lesson->id]) }}";
        </script>
    @endpush
@endsection
