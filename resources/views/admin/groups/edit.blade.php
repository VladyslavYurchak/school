@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Редагування групи: "{{ $group->name }}"</h2>
                <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Назад до списку
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Форма редагування групи --}}
            <form action="{{ route('admin.groups.update', $group->id) }}" method="POST" class="mb-4">
                @csrf
                @method('PUT')

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Назва групи</label>
                                <input
                                    type="text"
                                    name="name"
                                    class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name', $group->name) }}"
                                    required
                                >
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- NEW: Тип групи --}}
                            <div class="col-md-3">
                                <label class="form-label">Тип групи</label>
                                <select
                                    name="type"
                                    class="form-select @error('type') is-invalid @enderror"
                                >
                                    <option value="group" @selected(old('type', $group->type) === 'group')>Групова</option>
                                    <option value="pair"  @selected(old('type', $group->type) === 'pair')>Парна</option>
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text">
                                    Тип визначає, кого можна додавати (перевіряється тип абонементу студента).
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Викладач</label>
                                <select
                                    name="teacher_id"
                                    class="form-select @error('teacher_id') is-invalid @enderror"
                                    required
                                >
                                    <option value="">Оберіть викладача</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" @selected(old('teacher_id', $group->teacher_id) == $teacher->id)>
                                            {{ $teacher->full_name ?? ($teacher->user->name ?? 'Ім’я не вказано') }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('teacher_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Нотатки</label>
                                <textarea
                                    name="notes"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    rows="3"
                                >{{ old('notes', $group->notes) }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary">Зберегти зміни</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            {{-- Список студентів --}}
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Студенти у групі</span>

                    @php
                        // якщо контролер передає $canAddMore — використовуємо його;
                        // інакше для pair блокуємо, якщо у групі >=2 студентів
                        $canAddMoreLocal = isset($canAddMore)
                            ? $canAddMore
                            : !($group->type === 'pair' && $group->students->count() >= 2);
                    @endphp

                    <button class="btn btn-sm btn-success"
                            data-bs-toggle="modal"
                            data-bs-target="#addStudentModal"
                        @disabled(!$canAddMoreLocal)>
                        + Додати студента
                    </button>
                </div>

                <div class="card-body table-responsive">
                    @if(!$canAddMoreLocal && $group->type === 'pair')
                        <div class="alert alert-warning">
                            У парній групі може бути не більше двох студентів.
                        </div>
                    @endif

                    @if($group->students->isEmpty())
                        <p>Немає студентів у цій групі.</p>
                    @else
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Ім’я</th>
                                <th>Прізвище</th>
                                <th>Абонемент</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th class="text-end">Дія</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($group->students as $student)
                                <tr>
                                    <td>{{ $student->first_name }}</td>
                                    <td>{{ $student->last_name }}</td>
                                    <td>
                                        @if(optional($student->subscriptionTemplate)->type)
                                            <span class="badge bg-info">
                                                    {{ optional($student->subscriptionTemplate)->type }}
                                                </span>
                                        @else
                                            <span class="badge bg-secondary">нема</span>
                                        @endif
                                    </td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->phone }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.groups.remove-student', [$group->id, $student->id]) }}"
                                              method="POST"
                                              onsubmit="return confirm('Видалити студента з групи?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-x"></i> Видалити
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

        </div>

        {{-- Модалка додавання студента --}}
        <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form action="{{ route('admin.groups.add-student', $group->id) }}" method="POST" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="addStudentModalLabel">Додати студента до групи</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Оберіть студента</label>
                        <select name="student_id" class="form-select" required @disabled(!$canAddMoreLocal)>
                            <option value="">-- Оберіть студента --</option>
                            @foreach($availableStudents as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->first_name }} {{ $student->last_name }}
                                    @if(optional($student->subscriptionTemplate)->type)
                                        — {{ optional($student->subscriptionTemplate)->type }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">
                            Додаються лише студенти з активним абонементом відповідного типу ({{ $group->type }}).
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" @disabled(!$canAddMoreLocal)>Додати</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
@endsection
