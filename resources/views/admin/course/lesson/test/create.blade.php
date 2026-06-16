@extends('admin.layouts.layout')

@section('content')
    <div class="test-block-page">
        <div class="card shadow-lg border-0 mb-4">
            <div class="card-header bg-white d-flex align-items-center">
                <h3 class="fw-bold text-dark mb-0">{{ $lesson->title }} - тестовий блок</h3>
                <a href="{{ route('admin.course.show', $lesson->course_id) }}"
                   class="btn btn-outline-secondary btn-sm ms-auto">
                    Назад
                </a>
            </div>

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

            <div class="mb-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-white">
                        <h5 class="fw-bold text-dark mb-0">Додати тестове питання</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.course.lesson.test.store', $lesson->id) }}">
                            @csrf

                            <div class="mb-3">
                                <label for="question" class="form-label fw-bold">Питання</label>
                                <textarea name="question" id="question" class="form-control" rows="3">{{ old('question') }}</textarea>
                                @if ($errors->has('question'))
                                    <div class="alert alert-danger mt-1">
                                        {{ $errors->first('question') }}
                                    </div>
                                @endif
                            </div>

                            @php
                                $oldOptions = old('options.new', []);
                                $defaultCount = min(5, max(3, count($oldOptions)));
                            @endphp

                            <div class="mb-3">
                                <label class="form-label fw-bold">Варіанти відповідей</label>
                                <div class="form-text mb-2">Мінімум 3 відповіді, максимум 5. Позначте одну або кілька правильних.</div>
                                <div class="options-container">
                                    @for ($i = 0; $i < $defaultCount; $i++)
                                        <div class="option-row" data-index="{{ $i }}">
                                            <input
                                                type="text"
                                                name="options[new][{{ $i }}][option_text]"
                                                class="form-control me-2 option-input"
                                                placeholder="Варіант відповіді"
                                                value="{{ old("options.new.$i.option_text") }}"
                                                maxlength="1000"
                                            />
                                            <label class="custom-checkbox">
                                                <input
                                                    type="checkbox"
                                                    name="options[new][{{ $i }}][is_correct]"
                                                    value="1"
                                                    {{ old("options.new.$i.is_correct") ? 'checked' : '' }}
                                                >
                                                <span class="checkmark"></span> Правильна
                                            </label>
                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm remove-option"
                                                data-index="{{ $i }}"
                                            >
                                                Видалити
                                            </button>
                                        </div>
                                    @endfor
                                </div>
                            </div>

                            <div class="mb-3">
                                <button type="button" class="btn btn-success shadow-sm" id="add-option">
                                    Додати відповідь
                                </button>
                            </div>

                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary shadow-sm">
                                    Зберегти
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @include('admin.course.lesson.test.list', compact('tests'))
        @include('admin.course.lesson.test.list-test', ['tests' => $tests, 'lesson' => $lesson])
    </div>

    @push('scripts')
        <script>
            const updateOrderUrl = "{{ route('admin.course.lesson.test.updateOrder', ['lesson' => $lesson->id]) }}";
        </script>
    @endpush
@endsection
