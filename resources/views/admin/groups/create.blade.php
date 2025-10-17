@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Додати групу</h2>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-secondary">Назад до списку груп</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.groups.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Назва групи</label>
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

                {{-- NEW: Тип групи --}}
                <div class="mb-3">
                    <label for="type" class="form-label">Тип групи</label>
                    <select
                        name="type"
                        id="type"
                        class="form-select @error('type') is-invalid @enderror"
                    >
                        @php
                            $selectedType = old('type', 'group'); // дефолт — group
                        @endphp
                        <option value="group" @selected($selectedType === 'group')>Групова</option>
                        <option value="pair"  @selected($selectedType === 'pair')>Парна</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Тип визначає, яких студентів можна додавати (перевіряється їх абонемент).</div>
                </div>

                <div class="mb-3">
                    <label for="teacher_id" class="form-label">Викладач</label>
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

                <div class="mb-3">
                    <label for="notes" class="form-label">Нотатки</label>
                    <textarea
                        name="notes"
                        id="notes"
                        class="form-control @error('notes') is-invalid @enderror"
                        rows="3"
                    >{{ old('notes') }}</textarea>
                    @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary">Створити групу</button>
            </form>
        </div>
    </main>
@endsection
