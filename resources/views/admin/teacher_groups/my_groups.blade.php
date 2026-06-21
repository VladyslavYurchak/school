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
                            <i class="bi bi-collection"></i>
                            Викладач
                        </span>
                        <h1 class="admin-title">Мої групи</h1>
                        <p class="admin-subtitle">Групи та пари, закріплені за вашим профілем викладача.</p>
                    </div>

                    <div class="admin-actions">
                        <span class="admin-badge admin-badge-muted">Усього: {{ $groups->count() }}</span>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список груп</h2>
                </div>

                <div class="admin-panel-body">
                    @if($groups->isEmpty())
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-collection"></i>
                            </div>
                            <h3>Груп поки немає</h3>
                            <p>Тут зʼявляться групи або пари, які адміністратор закріпить за вами.</p>
                        </div>
                    @else
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table-sm w-100" id="groups-table">
                                <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Тип</th>
                                    <th>Студентів</th>
                                    <th>Нотатки</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($groups as $group)
                                    <tr>
                                        <td>{{ $group->name ?? '-' }}</td>
                                        <td>
                                            <span class="admin-badge admin-badge-muted">
                                                {{ $group->type === 'pair' ? 'Пара' : 'Група' }}
                                            </span>
                                        </td>
                                        <td>{{ $group->students_count ?? 0 }}</td>
                                        <td>{{ $group->notes ?? '-' }}</td>
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
            $('#groups-table').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/uk.json'
                },
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
