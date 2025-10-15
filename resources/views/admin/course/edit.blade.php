@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h3 class="mb-0 fw-bold text-dark">Редагувати курс: {{ $course->title }}</h3>
            </div>

            <div class="card-body">
                <form action="{{ route('admin.course.update', $course->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    {{-- Назва --}}
                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Назва курсу</label>
                        <input type="text"
                               class="form-control"
                               id="title"
                               name="title"
                               value="{{ old('title', $course->title) }}"
                               required>
                    </div>

                    {{-- Опис --}}
                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Опис курсу</label>
                        <textarea class="form-control"
                                  id="description"
                                  name="description"
                                  rows="4"
                                  required>{{ old('description', $course->description) }}</textarea>
                    </div>

                    {{-- Мова --}}
                    <div class="mb-3">
                        <label for="language_id" class="form-label fw-semibold">Мова курсу</label>
                        <select class="form-select" id="language_id" name="language_id" required>
                            @foreach($languages as $language)
                                <option value="{{ $language->id }}" {{ old('language_id', $course->language_id) == $language->id ? 'selected' : '' }}>
                                    {{ $language->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Ціна --}}
                    <div class="mb-3">
                        <label for="price" class="form-label fw-semibold">Ціна (грн)</label>
                        <input type="number"
                               class="form-control"
                               id="price"
                               name="price"
                               value="{{ old('price', $course->price) }}"
                               step="0.01"
                               required>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.course.index') }}" class="btn btn-outline-secondary me-2">Скасувати</a>
                        <button type="submit" class="btn btn-primary">Оновити курс</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
