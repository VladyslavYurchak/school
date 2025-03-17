@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="card">
            <div class="card-header">
                <h3>Створити курс</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.course.store') }}" method="POST">
                    @csrf
                    <!-- Назва курсу -->
                    <div class="mb-3">
                        <label for="title" class="form-label">Назва курсу</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <!-- Опис курсу -->
                    <div class="mb-3">
                        <label for="description" class="form-label">Опис курсу</label>
                        <textarea class="form-control" id="description" name="description" required></textarea>
                    </div>
                    <!-- Вибір мови -->
                    <div class="mb-3">
                        <label for="language" class="form-label">Мова курсу</label>
                        <select class="form-select" id="language" name="language_id" required>
                            <option value="">Оберіть мову</option>
                            @foreach($languages as $language)
                                <option value="{{ $language->id }}">{{ $language->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <!-- Можна додати додаткові поля за потребою -->
                    <button type="submit" class="btn btn-primary">Створити курс</button>
                </form>
            </div>
        </div>
    </main>
@endsection
