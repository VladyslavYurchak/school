@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-pencil"></i> Соцмережі</span>
                        <h1 class="admin-title">{{ $publication->title }}</h1>
                        <p class="admin-subtitle">Редагування чернетки та перевірка окремих результатів.</p>
                    </div>
                    <div class="admin-actions">
                        <a href="{{ route('admin.social-publishing.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i> До журналу
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('admin.social-publishing.update', $publication) }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="admin-panel admin-form admin-form-card mb-3">
                @csrf
                @method('PATCH')
                @include('admin.social-publishing._form', ['publication' => $publication])
            </form>

            <section class="admin-panel admin-form-card">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Тестовий журнал</h2>
                    <span class="admin-badge admin-badge-muted">{{ $publication->targets->count() }} мережі</span>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-table-wrap mb-3">
                        <table class="table admin-table mb-0">
                            <thead>
                            <tr>
                                <th>Мережа</th>
                                <th>Статус</th>
                                <th>Остання спроба</th>
                                <th>Помилка</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($publication->targets as $target)
                                <tr>
                                    <td class="text-capitalize">{{ $target->platform }}</td>
                                    <td>
                                        <span class="admin-badge {{ $target->status === 'simulated' ? 'admin-badge-free' : 'admin-badge-muted' }}">
                                            {{ $target->status === 'simulated' ? 'Перевірено' : 'Очікує' }}
                                        </span>
                                    </td>
                                    <td>{{ $target->attempted_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                    <td>{{ $target->error_message ?? '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <form action="{{ route('admin.social-publishing.publish', $publication) }}" method="POST">
                        @csrf
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-play-circle"></i>
                            Тестова публікація
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>
@endsection
