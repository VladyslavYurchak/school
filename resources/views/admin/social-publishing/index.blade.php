@extends('admin.layouts.layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/social-publishing.css') }}">
@endpush

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-share"></i> Експериментальний модуль</span>
                        <h1 class="admin-title">Соцмережі</h1>
                        <p class="admin-subtitle">
                            Окремі чернетки для Facebook, Instagram і TikTok. Зараз модуль працює без реальної відправки.
                        </p>
                    </div>
                    <div class="admin-actions">
                        <a href="{{ route('admin.social-publishing.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i> Нова чернетка
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="social-safety-banner mb-3">
                <i class="bi bi-shield-lock"></i>
                <div>
                    <strong>Безпечний тестовий режим активний.</strong>
                    <div>Цей розділ не використовує пости сайту та не звертається до API соцмереж.</div>
                </div>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Чернетки й журнал</h2>
                    <span class="admin-badge admin-badge-muted">Усього: {{ $publications->total() }}</span>
                </div>
                <div class="admin-panel-body p-0">
                    @if($publications->count())
                        <div class="admin-table-wrap border-0 rounded-0">
                            <table class="table admin-table mb-0">
                                <thead>
                                <tr>
                                    <th>Назва</th>
                                    <th>Мережі</th>
                                    <th>Статус</th>
                                    <th>Оновлено</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($publications as $publication)
                                    <tr>
                                        <td><strong>{{ $publication->title }}</strong></td>
                                        <td>
                                            <div class="social-target-list">
                                                @foreach($publication->targets as $target)
                                                    <span class="admin-badge admin-badge-muted text-capitalize">{{ $target->platform }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>
                                            <span class="admin-badge {{ $publication->status === 'simulated' ? 'admin-badge-free' : 'admin-badge-muted' }}">
                                                {{ $publication->status === 'simulated' ? 'Тест пройдено' : 'Чернетка' }}
                                            </span>
                                        </td>
                                        <td>{{ $publication->updated_at->format('d.m.Y H:i') }}</td>
                                        <td>
                                            <div class="admin-inline-actions justify-content-end">
                                                <a href="{{ route('admin.social-publishing.edit', $publication) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Відкрити
                                                </a>
                                                <form action="{{ route('admin.social-publishing.delete', $publication) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Видалити цю чернетку та її тестовий журнал?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Видалити">
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
                        <div class="p-3">{{ $publications->links('admin.pagination.pagination') }}</div>
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon"><i class="bi bi-share"></i></div>
                            <h2 class="h5">Чернеток ще немає</h2>
                            <p class="mb-0">Створіть першу публікацію та безпечно перевірте її для трьох мереж.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
