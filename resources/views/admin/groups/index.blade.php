@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2>Групи</h2>
                <a href="{{ route('admin.groups.create') }}" class="btn btn-success">+ Додати групу</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>Назва групи</th>
                            <th>Викладач</th>
                            <th>К-сть учнів</th>
                            <th>Нотатки</th>
                            <th>Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($groups as $group)
                            <tr>
                                <td>{{ $group->name }}</td>
                                <td>{{ $group->teacher?->full_name ?? '—' }}</td>
                                <td>{{ $group->students->count() }}</td>
                                <td class="text-truncate" style="max-width: 200px;">{{ $group->notes }}</td>
                                <td>
                                    <a href="{{ route('admin.groups.edit', $group) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Видалити групу?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
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
