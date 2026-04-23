@extends('admin.layouts.layout')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Правила школи</h1>
            <a href="{{ route('admin.school-rules.create') }}" class="btn btn-primary">Додати правило</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card">
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Назва</th>
                        <th>Порядок</th>
                        <th>Активне</th>
                        <th class="text-end">Дії</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rules as $rule)
                        <tr>
                            <td>{{ $rule->id }}</td>
                            <td>{{ $rule->title }}</td>
                            <td>{{ $rule->sort_order }}</td>
                            <td>{{ $rule->is_active ? 'Так' : 'Ні' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.school-rules.edit', $rule) }}" class="btn btn-sm btn-outline-primary">
                                    Редагувати
                                </a>

                                <form action="{{ route('admin.school-rules.destroy', $rule) }}"
                                      method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm('Видалити правило?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Видалити
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">Правил поки немає.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
