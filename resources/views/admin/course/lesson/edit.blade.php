@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-pencil"></i>
                            Редагування
                        </span>
                        <h1 class="admin-title">Редагувати урок</h1>
                        <p class="admin-subtitle">
                            {{ $lesson->title }}
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.lesson.show', $lesson->id) }}" class="admin-btn-soft">
                            <i class="bi bi-eye"></i>
                            Перегляд
                        </a>
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До курсу
                        </a>
                    </div>
                </div>
            </section>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.course.lesson.update', $lesson) }}" method="POST" class="admin-panel admin-form admin-form-card">
                @csrf
                @method('PUT')

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані уроку</h2>
                    <span class="admin-badge {{ $lesson->is_published ? 'admin-badge-free' : 'admin-badge-muted' }}">
                        {{ $lesson->is_published ? 'Опублікований' : 'Чернетка' }}
                    </span>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="lesson_type" class="admin-form-label">Вид уроку</label>
                        <select id="lesson_type" name="lesson_type" class="form-select">
                            @foreach(['Reading', 'Listening', 'Grammar', 'Speaking', 'Test'] as $type)
                                <option value="{{ $type }}" @selected(old('lesson_type', $lesson->lesson_type) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="admin-form-section">
                        <label for="title" class="admin-form-label">Назва уроку</label>
                        <input type="text" name="title" id="title" class="form-control" value="{{ old('title', $lesson->title) }}" required>
                    </div>

                    <div class="admin-form-section">
                        <label for="description" class="admin-form-label">Опис уроку</label>
                        <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $lesson->description) }}</textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="admin-form-section">
                                <label for="position" class="admin-form-label">Номер уроку</label>
                                <input type="number" name="position" id="position" class="form-control" min="0" value="{{ old('position', $lesson->position) }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="admin-form-section">
                                <label for="price" class="admin-form-label">Ціна окремого уроку</label>
                                <input type="number" name="price" id="price" class="form-control" min="0" step="0.01" value="{{ old('price', $lesson->price) }}" placeholder="Наприклад: 300">
                                <div class="form-text">Порожньо — урок доступний тільки у складі курсу. 0 — безкоштовний відкритий урок.</div>
                            </div>
                        </div>
                    </div>

                    <div class="admin-form-section">
                        <div class="form-check form-switch admin-switch">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" id="is_published" value="1" class="form-check-input" @checked(old('is_published', $lesson->is_published))>
                            <label for="is_published" class="form-check-label">Опублікований</label>
                        </div>
                    </div>

                    <div class="admin-form-actions">
                        <a href="{{ route('admin.course.show', $lesson->course_id) }}" class="admin-btn-soft">
                            <i class="bi bi-x-lg"></i>
                            Скасувати
                        </a>
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Зберегти
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
