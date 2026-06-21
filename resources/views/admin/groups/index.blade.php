@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-collection"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Групи</h1>
                        <p class="admin-subtitle">Навчальні групи та пари, їхні викладачі, склад і нотатки.</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.groups.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Додати групу
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список груп</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $groups->count() }}</span>
                </div>

                <div class="admin-panel-body">
                    @if($groups->count())
                        <div class="admin-table-wrap">
                            <table class="table admin-table admin-teacher-table">
                                <thead>
                                <tr>
                                    <th>Назва групи</th>
                                    <th>Тип</th>
                                    <th>Викладач</th>
                                    <th>Учнів</th>
                                    <th>Нотатки</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($groups as $group)
                                    <tr>
                                        <td>{{ $group->name }}</td>
                                        <td>
                                            <span class="admin-badge admin-badge-muted">
                                                {{ $group->type === 'pair' ? 'Пара' : 'Група' }}
                                            </span>
                                        </td>
                                        <td>{{ $group->teacher?->full_name ?? '-' }}</td>
                                        <td>{{ $group->students_count ?? 0 }}</td>
                                        <td class="admin-note-truncate" title="{{ $group->notes }}">{{ $group->notes ?? '-' }}</td>
                                        <td class="text-end">
                                            <div class="admin-compact-actions">
                                                <a href="{{ route('admin.groups.edit', $group) }}" class="admin-btn-warning" title="Редагувати">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('admin.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('Видалити групу?')">
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
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-collection"></i>
                            </div>
                            <h3>Груп поки немає</h3>
                            <p>Створіть першу групу або пару, щоб закріпити її за викладачем.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
