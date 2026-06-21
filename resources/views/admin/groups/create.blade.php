@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-people"></i>
                            Групи
                        </span>
                        <h1 class="admin-title">Додати групу</h1>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.groups.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            Назад до списку груп
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

            <form action="{{ route('admin.groups.store') }}" method="POST" class="admin-panel admin-form admin-form-card">
                @csrf

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані групи</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="name" class="admin-form-label">Назва групи</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name') }}"
                            required
                            maxlength="255"
                            placeholder="Введіть назву групи"
                        >
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="admin-form-section">
                        <label for="type" class="admin-form-label">Тип групи</label>
                        <select
                            name="type"
                            id="type"
                            class="form-select @error('type') is-invalid @enderror"
                        >
                            @php
                                $selectedType = old('type', 'group');
                            @endphp
                            <option value="group" @selected($selectedType === 'group')>Групова</option>
                            <option value="pair"  @selected($selectedType === 'pair')>Парна</option>
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">Тип визначає, яких студентів можна додавати (перевіряється їх абонемент).</div>
                    </div>

                    <div class="admin-form-section">
                        <label for="teacher_id" class="admin-form-label">Викладач</label>
                        <select
                            name="teacher_id"
                            id="teacher_id"
                            class="form-select @error('teacher_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Оберіть викладача</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>
                                    {{ $teacher->first_name }} {{ $teacher->last_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('teacher_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="admin-form-section">
                        <label for="notes" class="admin-form-label">Нотатки</label>
                        <textarea
                            name="notes"
                            id="notes"
                            class="form-control @error('notes') is-invalid @enderror"
                            rows="3"
                        >{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="admin-form-actions">
                        <a href="{{ route('admin.groups.index') }}" class="admin-btn-soft">
                            Скасувати
                        </a>
                        <button type="submit" class="admin-btn-primary">Створити групу</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
