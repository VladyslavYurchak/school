@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-calendar-event"></i>
                            Сайт
                        </span>
                        <h1 class="admin-title">Події</h1>
                        <p class="admin-subtitle">
                            Керуйте подіями школи. Майбутні опубліковані події показуються на головній сторінці.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.event.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Створити подію
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список подій</h2>
                    <span class="admin-badge admin-badge-muted">
                        Усього: {{ $events->total() }}
                    </span>
                </div>

                <div class="admin-panel-body p-0">
                    @if($events->count())
                        <div class="admin-table-wrap border-0 rounded-0">
                            <table class="table admin-table mb-0">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Назва</th>
                                    <th>Дата події</th>
                                    <th>Статус</th>
                                    <th>Створено</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($events as $event)
                                    <tr>
                                        <td>{{ $event->id }}</td>
                                        <td>
                                            <a href="{{ route('admin.event.show', $event) }}" class="admin-course-link">
                                                {{ $event->title }}
                                            </a>
                                        </td>
                                        <td>{{ $event->start_date->format('d.m.Y') }}</td>
                                        <td>
                                            @if($event->is_published)
                                                <span class="admin-badge admin-badge-free">Опубліковано</span>
                                            @else
                                                <span class="admin-badge admin-badge-muted">Чернетка</span>
                                            @endif
                                        </td>
                                        <td>{{ $event->created_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <div class="admin-inline-actions justify-content-end">
                                                <a href="{{ route('admin.event.show', $event) }}" class="btn btn-sm btn-outline-secondary">
                                                    <i class="bi bi-eye"></i>
                                                    Перегляд
                                                </a>
                                                <a href="{{ route('admin.event.edit', $event) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                    Редагувати
                                                </a>
                                                <form action="{{ route('admin.event.delete', $event) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Видалити цю подію?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                        Видалити
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="p-3">
                            {{ $events->onEachSide(2)->links('admin.pagination.pagination') }}
                        </div>
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <h2 class="h5">Подій поки немає</h2>
                            <p class="mb-0">Створіть першу подію, щоб вона могла зʼявитися на головній.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
