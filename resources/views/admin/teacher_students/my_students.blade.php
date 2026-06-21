@extends('admin.layouts.layout')

@section('styles')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
@endsection

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-people"></i>
                            Викладач
                        </span>
                        <h1 class="admin-title">Мої студенти</h1>
                        <p class="admin-subtitle">Активні студенти, закріплені за вашим профілем викладача.</p>
                    </div>

                    <div class="admin-actions">
                        <span class="admin-badge admin-badge-muted">Усього: {{ $students->count() }}</span>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список студентів</h2>
                </div>

                <div class="admin-panel-body">
                    @if($students->isEmpty())
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-person"></i>
                            </div>
                            <h3>Студентів поки немає</h3>
                            <p>Тут зʼявляться активні студенти, яких адміністратор закріпить за вами.</p>
                        </div>
                    @else
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table w-100" id="students-table">
                                <thead>
                                <tr>
                                    <th>Імʼя</th>
                                    <th>Прізвище</th>
                                    <th>Email</th>
                                    <th>Телефон</th>
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
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function() {
            $('#students-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[1, 'asc']],
                dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        });
    </script>
@endsection
