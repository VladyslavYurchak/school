@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-ticket-perforated"></i>
                            Абонементи
                        </span>
                        <h1 class="admin-title">Створити тип абонементу</h1>
                    </div>
                </div>
            </section>

        <form action="{{ route('admin.subscription-templates.store') }}" method="POST" class="admin-panel admin-form admin-form-card">
            @csrf

            <div class="admin-panel-header">
                <h2 class="admin-panel-title">Дані абонементу</h2>
            </div>

            <div class="admin-panel-body">
                <div class="admin-form-section">
                    <label for="title" class="admin-form-label">Назва абонементу</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>

                <div class="admin-form-section">
                    <label for="type" class="admin-form-label">Тип занять</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="individual">Індивідуальні</option>
                        <option value="group">Групові</option>
                    </select>
                </div>

                <div class="admin-form-section">
                    <label for="lessons_per_week" class="admin-form-label">Кількість занять на тиждень</label>
                    <input type="number" class="form-control" id="lessons_per_week" name="lessons_per_week" required min="1" max="7">
                </div>

                <div class="admin-form-section">
                    <label for="price" class="admin-form-label">Ціна (грн)</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                </div>

                <div class="admin-form-section">
                    <label for="description" class="admin-form-label">Опис (опціонально)</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>

                <div class="admin-form-actions">
                    <button type="submit" class="admin-btn-primary">Зберегти</button>
                </div>
            </div>
        </form>
        </div>
    </div>
@endsection
