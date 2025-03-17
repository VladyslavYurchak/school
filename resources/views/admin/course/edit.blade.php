@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card">
            <div class="card-header">
                <h3>Редагувати курс: {{ $course->title }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.course.update', $course->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label for="title" class="form-label">Назва курсу</label>
                        <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $course->title) }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Опис курсу</label>
                        <textarea class="form-control" id="description" name="description" required>{{ old('description', $course->description) }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label for="language_id" class="form-label">Мова курсу</label>
                        <select class="form-select" id="language_id" name="language_id" required>
                            @foreach($languages as $language)
                                <option value="{{ $language->id }}" {{ old('language_id', $course->language_id) == $language->id ? 'selected' : '' }}>
                                    {{ $language->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Оновити курс</button>
                </form>
            </div>
        </div>
    </main>
@endsection
