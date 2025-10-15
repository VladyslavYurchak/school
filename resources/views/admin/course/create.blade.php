@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light border-bottom">
                <h3 class="mb-0 text-dark">Створити курс</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.course.store') }}" method="POST">
                    @csrf

                    <!-- Назва курсу -->
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Назва курсу</label>
                        <input type="text" class="form-control border-secondary" id="title" name="title" required>
                    </div>

                    <!-- Опис курсу -->
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Опис курсу</label>
                        <textarea class="form-control border-secondary" id="description" name="description" rows="4" required></textarea>
                    </div>

                    <!-- Вибір мови -->
                    <div class="mb-3">
                        <label for="language" class="form-label fw-semibold">Мова курсу</label>
                        <select class="form-select border-secondary" id="language" name="language_id" required>
                            <option value="">Оберіть мову</option>
                            @foreach($languages as $language)
                                <option value="{{ $language->id }}">{{ $language->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Кнопки -->
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.course.index') }}" class="btn btn-outline-secondary me-2">Скасувати</a>
                        <button type="submit" class="btn btn-outline-dark">Створити курс</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <style>
        .form-control, .form-select {
            border-radius: 6px;
            box-shadow: none;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6c757d;
            box-shadow: 0 0 0 0.1rem rgba(108,117,125,.25);
        }
        .btn {
            border-radius: 6px;
        }
    </style>
@endsection
