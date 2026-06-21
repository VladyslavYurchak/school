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
                        <h1 class="admin-title">Редагувати курс</h1>
                        <p class="admin-subtitle">
                            {{ $course->title }}
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.course.show', $course) }}" class="admin-btn-soft">
                            <i class="bi bi-eye"></i>
                            Перегляд
                        </a>
                        <a href="{{ route('admin.course.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До курсів
                        </a>
                    </div>
                </div>
            </section>

            <form action="{{ route('admin.course.update', $course->id) }}" method="POST" class="admin-panel admin-form admin-form-card">
                @csrf
                @method('PUT')

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані курсу</h2>
                    <span class="admin-badge {{ $course->is_published ? 'admin-badge-free' : 'admin-badge-muted' }}">
                        {{ $course->is_published ? 'Опублікований' : 'Чернетка' }}
                    </span>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="title" class="admin-form-label">Назва курсу</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $course->title) }}" required>
                    </div>

                    <div class="admin-form-section">
                        <label for="description" class="admin-form-label">Опис курсу</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required>{{ old('description', $course->description) }}</textarea>
                    </div>

                    <div class="admin-form-section">
                        <label for="language_id" class="admin-form-label">Мова курсу</label>
                        <select class="form-select" id="language_id" name="language_id" required>
                            @foreach($languages as $language)
                                <option value="{{ $language->id }}" @selected(old('language_id', $course->language_id) == $language->id)>
                                    {{ $language->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="admin-form-section">
                        <label for="price" class="admin-form-label">Ціна (грн)</label>
                        <input type="number" class="form-control" id="price" name="price" value="{{ old('price', $course->price) }}" min="0" step="0.01" required>
                    </div>

                    <div class="admin-form-section">
                        <div class="form-check form-switch admin-switch">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" id="is_published" value="1" class="form-check-input" @checked(old('is_published', $course->is_published))>
                            <label for="is_published" class="form-check-label">Опублікований</label>
                        </div>
                    </div>

                    <div class="admin-form-actions">
                        <a href="{{ route('admin.course.index') }}" class="admin-btn-soft">
                            <i class="bi bi-x-lg"></i>
                            Скасувати
                        </a>
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Оновити курс
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
