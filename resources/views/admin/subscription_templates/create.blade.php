@extends('admin.layouts.layout')

@section('content')
    <div class="container">
        <h2>Створити тип абонементу</h2>

        <form action="{{ route('admin.subscription-templates.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="title" class="form-label">Назва абонементу</label>
                <input
                    type="text"
                    class="form-control"
                    id="title"
                    name="title"
                    value="{{ old('title') }}"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Тип занять</label>
                <select class="form-control" id="type" name="type" required>
                    <option value="individual" {{ old('type') === 'individual' ? 'selected' : '' }}>Індивідуальні</option>
                    <option value="group" {{ old('type') === 'group' ? 'selected' : '' }}>Групові</option>
                    <option value="pair" {{ old('type') === 'pair' ? 'selected' : '' }}>Парні</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="lessons_per_week" class="form-label">Кількість занять на тиждень</label>
                <input
                    type="number"
                    class="form-control"
                    id="lessons_per_week"
                    name="lessons_per_week"
                    value="{{ old('lessons_per_week') }}"
                    required
                    min="1"
                    max="7"
                >
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Ціна (грн)</label>
                <input
                    type="number"
                    class="form-control"
                    id="price"
                    name="price"
                    value="{{ old('price') }}"
                    step="0.01"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary">Зберегти</button>
        </form>
    </div>
@endsection
