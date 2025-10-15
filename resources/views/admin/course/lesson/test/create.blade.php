@extends('admin.layouts.layout')


@section('content')
    <main class="app-main">
        <div class="card shadow-lg border-0 mb-4">
            <div class="card-header bg-white d-flex align-items-center">
                <h3 class="fw-bold text-dark mb-0">{{ $lesson->title }} – тестовий блок</h3>
                <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="btn btn-outline-secondary btn-sm ms-auto">
                    ← Назад
                </a>
            </div>




            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрити"></button>
                    </div>
                @endif

                @if ($errors->has('options'))
                        <div class="alert alert-danger">
                            {{ $errors->first('options') }}
                        </div>
                @endif

                    <div class="mb-4">
                        <div class="card shadow-lg border-0">
                            <div class="card-header bg-white">
                                <h5 class="fw-bold text-dark mb-0">Форма додавання тестового питання</h5>
                            </div>
                            <div class="card-body">

                            <form method="POST" action="{{ route('admin.course.lesson.test.store', $lesson->id) }}">
                                    @csrf
                                    <!-- Текст питання -->
                                    <div class="mb-3">
                                        <label for="question" class="form-label fw-bold">Введіть питання до тесту:</label>
                                        <textarea name="question" id="question" class="form-control" rows="3">{{ old('question', $test->question ?? '') }}</textarea>
                                        @if ($errors->has('question'))
                                            <div class="alert alert-danger mt-1">
                                                {{ $errors->first('question') }}
                                            </div>
                                        @endif
                                    </div>

                                <!-- Відповіді -->
                                @php
                                    $oldOptions = old('options.new', []);
                                    $defaultCount = count($oldOptions) > 0 ? count($oldOptions) : 3;
                                @endphp

                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Введіть варіанти відповідей:</label>
                                        <div class="options-container">
                                            @for ($i = 0; $i < $defaultCount; $i++)
                                                <div class="d-flex align-items-center mb-2" data-index="{{ $i }}">
                                                    <input
                                                        type="text"
                                                        name="options[new][{{ $i }}][option_text]"
                                                        class="form-control me-2 option-input"
                                                        placeholder="Варіант відповіді"
                                                        value="{{ old("options.new.$i.option_text") }}"
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
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-success shadow-sm" id="add-option">
                                        + Додати варіант
                                    </button>
                                </div>

                                <!-- Зберегти зміни -->
                                <div class="mb-3">
                                    <button type="submit" class="btn btn-primary shadow-sm">
                                        💾 Зберегти зміни
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @include('admin.course.lesson.test.list', compact('tests'))
        @include('admin.course.lesson.test.list-test', ['tests' => $tests, 'lesson' => $lesson])

    </main>
    <script>
        const updateOrderUrl = "{{ route('admin.course.lesson.test.updateOrder') }}";
    </script>
    <script src="{{ asset('admin/course/lesson/test/sortable-tests.js') }}"></script>
    <script src="{{ asset('admin/course/lesson/test/test-options.js') }}"></script>
    <link href="{{ asset('admin/course/lesson/test/test-options.css') }}" rel="stylesheet">
    <script src="{{ asset('admin/course/lesson/test/sortable-tests.js') }}"></script>
@endsection
