@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Мої студенти</h2>
                {{-- Можна додати кнопку для додавання студента, якщо потрібно --}}
                {{-- <a href="{{ route('admin.students.create') }}" class="btn btn-success">+ Додати студента</a> --}}
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($students->isEmpty())
                <div class="alert alert-info">
                    У вас немає активних студентів.
                </div>
            @else
                <div class="card shadow-sm">
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="students-table">
                            <thead class="table-light">
                            <tr>
                                <th>Ім'я</th>
                                <th>Прізвище</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                {{-- Можна додати колонки, наприклад, Статус, Дії, якщо потрібно --}}
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td>{{ $student->first_name }}</td>
                                    <td>{{ $student->last_name }}</td>
                                    <td>
                                        <a href="mailto:{{ $student->email }}" class="text-decoration-none">
                                            {{ $student->email }}
                                        </a>
                                    </td>
                                    <td>{{ $student->phone ?? '-' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </main>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#students-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[1, 'asc']], // Сортування за прізвищем за замовчуванням
            });
        });
    </script>
@endsection
