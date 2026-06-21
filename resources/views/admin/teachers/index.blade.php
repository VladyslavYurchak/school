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
                            <i class="bi bi-person-workspace"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Викладачі</h1>
                        <p class="admin-subtitle">Профілі викладачів, ставки, контакти і статус активності.</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.teachers.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Додати викладача
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список викладачів</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $teachers->count() }}</span>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-table-wrap">
                        <table class="table admin-table admin-teacher-table-lg w-100" id="teachers-table">
                            <thead>
                            <tr>
                                <th>Прізвище</th>
                                <th>Імʼя</th>
                                <th>Телефон</th>
                                <th>Email</th>
                                <th>Індивідуальне</th>
                                <th>Групове</th>
                                <th>Парне</th>
                                <th>Пробне</th>
                                <th>Статус</th>
                                <th>Нотатки</th>
                                <th class="text-end">Дії</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($teachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->last_name }}</td>
                                    <td>{{ $teacher->first_name }}</td>
                                    <td>{{ $teacher->phone ?? '-' }}</td>
                                    <td>{{ $teacher->user->email ?? $teacher->email ?? '-' }}</td>
                                    <td>{{ $teacher->lesson_price ? number_format($teacher->lesson_price, 2) . ' грн' : '-' }}</td>
                                    <td>{{ $teacher->group_lesson_price ? number_format($teacher->group_lesson_price, 2) . ' грн' : '-' }}</td>
                                    <td>{{ $teacher->pair_lesson_price ? number_format($teacher->pair_lesson_price, 2) . ' грн' : '-' }}</td>
                                    <td>{{ $teacher->trial_lesson_price ? number_format($teacher->trial_lesson_price, 2) . ' грн' : '-' }}</td>
                                    <td>
                                        @if($teacher->is_active)
                                            <span class="admin-badge admin-badge-free">Активний</span>
                                        @else
                                            <span class="admin-badge admin-badge-muted">Архів</span>
                                        @endif
                                    </td>
                                    <td class="admin-note-truncate" title="{{ $teacher->note }}">{{ $teacher->note ?? '-' }}</td>
                                    <td class="text-end">
                                        <div class="admin-compact-actions">
                                            <a href="{{ route('admin.teachers.edit', $teacher->id) }}" class="admin-btn-warning" title="Редагувати">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('admin.teachers.destroy', $teacher->id) }}" method="POST" onsubmit="return confirm('Видалити цього викладача?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="admin-btn-danger" type="submit" title="Видалити">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(function () {
            $('#teachers-table').DataTable({
                language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json' },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']],
                dom: "<'row mb-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row mt-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>"
            });
        });
    </script>
@endsection
