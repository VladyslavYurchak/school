@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Викладачі</h2>
                <a href="{{ route('admin.teachers.create') }}" class="btn btn-success">+ Додати викладача</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover align-middle" id="teachers-table">
                        <thead class="table-light">
                        <tr>
                            <th>Прізвище</th>
                            <th>Ім’я</th>
                            <th>Телефон</th>
                            <th>Email</th>
                            <th>Ціна заняття</th>
                            <th>Активний</th>
                            <th>Нотатки</th>
                            <th>Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($teachers as $teacher)
                            <tr>
                                <td>{{ $teacher->last_name }}</td>
                                <td>{{ $teacher->first_name }}</td>
                                <td>{{ $teacher->phone ?? '-' }}</td>
                                <td>{{ $teacher->user->email ?? '-' }}</td>
                                <td>{{ $teacher->lesson_price ? number_format($teacher->lesson_price, 2) . ' ₴' : '-' }}</td>
                                <td>
                                    @if($teacher->is_active)
                                        <span class="badge bg-success">Так</span>
                                    @else
                                        <span class="badge bg-secondary">Ні</span>
                                    @endif
                                </td>
                                <td class="text-truncate" style="max-width: 150px;">{{ $teacher->note }}</td>
                                <td>
                                    <a href="{{ route('admin.teachers.edit', $teacher->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="{{ route('admin.teachers.destroy', $teacher->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Видалити цього викладача?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
@endsection

