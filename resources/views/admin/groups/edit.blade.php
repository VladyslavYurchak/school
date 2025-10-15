@extends('admin.layouts.layout')

@section('content')
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


            {{-- Форма редагування групи --}}
            <form action="{{ route('admin.groups.update', $group->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Назва групи</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $group->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Викладач</label>
                                <select name="teacher_id" class="form-select" required>
                                    <option value="">Оберіть викладача</option>
                                    @foreach($teachers as $teacher)
                                        <option value="{{ $teacher->id }}" @selected(old('teacher_id', $group->teacher_id) == $teacher->id)>
                                            {{ $teacher->full_name ?? $teacher->user->name ?? 'Ім’я не вказано' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Нотатки</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $group->notes) }}</textarea>
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
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        + Додати студента
                    </button>
                </div>

                <div class="card-body table-responsive">
                    @if($group->students->isEmpty())
                        <p>Немає студентів у цій групі.</p>
                    @else
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                            <tr>
                                <th>Ім’я</th>
                                <th>Прізвище</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($group->students as $student)
                                <tr>
                                    <td>{{ $student->first_name }}</td>
                                    <td>{{ $student->last_name }}</td>
                                    <td>{{ $student->email }}</td>
                                    <td>{{ $student->phone }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.groups.remove-student', [$group->id, $student->id]) }}" method="POST"
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
    </main>

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
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Оберіть студента --</option>
                        @foreach($availableStudents as $student)
                            <option value="{{ $student->id }}">{{ $student->first_name }} {{ $student->last_name }} ({{ $student->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Додати</button>
                </div>
            </form>
        </div>
    </div>
@endsection
