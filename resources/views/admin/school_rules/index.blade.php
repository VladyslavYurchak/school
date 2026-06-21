@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-list-check"></i>
                            Сайт
                        </span>
                        <h1 class="admin-title">Правила школи</h1>
                        <p class="admin-subtitle">
                            Керуйте правилами, які відображаються на публічній сторінці для учнів та батьків.
                            Активні правила показуються на сайті у вказаному порядку.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('rules.index') }}" class="admin-btn-soft" target="_blank" rel="noopener">
                            <i class="bi bi-eye"></i>
                            Переглянути на сайті
                        </a>
                        <a href="{{ route('admin.school-rules.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Додати правило
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Список правил</h2>
                    <span class="admin-badge admin-badge-muted">
                        Усього: {{ $rules->count() }}
                    </span>
                </div>

                <div class="admin-panel-body p-0">
                    @if($rules->count())
                        <div class="admin-table-wrap border-0 rounded-0">
                            <table class="table admin-table mb-0">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Назва</th>
                                    <th>Порядок</th>
                                    <th>Статус</th>
                                    <th class="text-end">Дії</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($rules as $rule)
                                    <tr>
                                        <td>{{ $rule->id }}</td>
                                        <td>
                                            <strong>{{ $rule->title }}</strong>
                                        </td>
                                        <td>{{ $rule->sort_order }}</td>
                                        <td>
                                            @if($rule->is_active)
                                                <span class="admin-badge admin-badge-free">Активне</span>
                                            @else
                                                <span class="admin-badge admin-badge-muted">Приховане</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="admin-inline-actions justify-content-end">
                                                <a href="{{ route('admin.school-rules.edit', $rule) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i>
                                                    Редагувати
                                                </a>

                                                <form action="{{ route('admin.school-rules.destroy', $rule) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Видалити правило?')">
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
                    @else
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon">
                                <i class="bi bi-list-check"></i>
                            </div>
                            <h2 class="h5">Правил поки немає</h2>
                            <p class="mb-0">Додайте перше правило, щоб воно зʼявилося на публічній сторінці.</p>
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
