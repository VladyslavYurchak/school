@extends('admin.layouts.layout')

@section('content')
    <div class="container">
        <h2>Створити тип абонементу</h2>

        <form action="{{ route('admin.subscription-templates.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label">Назва абонементу</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Тип занять</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="individual">Індивідуальні</option>
                    <option value="group">Групові</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="lessons_per_week" class="form-label">Кількість занять на тиждень</label>
                <input type="number" class="form-control" id="lessons_per_week" name="lessons_per_week" required min="1" max="7">
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Ціна (грн)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Опис (опціонально)</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Зберегти</button>
        </form>
    </div>
@endsection
