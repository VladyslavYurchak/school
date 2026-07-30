@extends('admin.layouts.layout')

@php
    $templateSections = [
        'individual' => ['title' => 'Індивідуальні абонементи', 'items' => $individualTemplates],
        'group' => ['title' => 'Групові абонементи', 'items' => $groupTemplates],
        'pair' => ['title' => 'Парні абонементи', 'items' => $pairTemplates ?? collect()],
    ];
    $totalTemplates = collect($templateSections)->sum(fn ($section) => $section['items']->count());
@endphp

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-ticket-perforated"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Абонементи</h1>
                        <p class="admin-subtitle">Шаблони оплат для індивідуальних, групових і парних занять.</p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.subscription-templates.create') }}" class="admin-btn-primary">
                            <i class="bi bi-plus-lg"></i>
                            Додати абонемент
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @foreach($templateSections as $type => $section)
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <h2 class="admin-panel-title">{{ $section['title'] }}</h2>
                        <span class="admin-badge admin-badge-muted">Усього: {{ $section['items']->count() }}</span>
                    </div>

                    <div class="admin-panel-body">
                        @if($section['items']->count())
                            <div class="admin-table-wrap">
                                <table class="table admin-table admin-teacher-table">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Назва</th>
                                        <th>Занять/тиждень</th>
                                        <th>Ціна</th>
                                        <th>Статус</th>
                                        <th>Створено</th>
                                        <th>Оновлено</th>
                                        <th class="text-end">Дії</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($section['items'] as $template)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $template->title }}</td>
                                            <td>{{ $template->lessons_per_week }}</td>
                                            <td>{{ number_format($template->price, 2, ',', ' ') }} грн</td>
                                            <td>
                                                <span class="admin-badge {{ $template->is_active ? 'admin-badge-free' : 'admin-badge-muted' }}">
                                                    {{ $template->is_active ? 'Активний' : 'Архів' }}
                                                </span>
                                            </td>
                                            <td>{{ $template->created_at->format('d.m.Y') }}</td>
                                            <td>{{ $template->updated_at->format('d.m.Y') }}</td>
                                            <td class="text-end">
                                                <div class="admin-actions justify-content-end">
                                                    <button
                                                        type="button"
                                                        class="admin-btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editSubscriptionModal"
                                                        data-id="{{ $template->id }}"
                                                        data-title="{{ $template->title }}"
                                                        data-type="{{ $template->type }}"
                                                        data-lessons="{{ $template->lessons_per_week }}"
                                                        data-price="{{ $template->price }}"
                                                        data-active="{{ $template->is_active ? '1' : '0' }}"
                                                        data-update-url="{{ route('admin.subscription-templates.update', $template) }}"
                                                    >
                                                        <i class="bi bi-pencil"></i>
                                                        Редагувати
                                                    </button>

                                                    @if($template->is_active)
                                                        <form action="{{ route('admin.subscription-templates.destroy', $template->id) }}"
                                                              method="POST"
                                                              onsubmit="return confirm('Перенести цей абонемент в архів? Прив’язки учнів та історія оплат залишаться.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="admin-btn-danger" type="submit">
                                                                <i class="bi bi-archive"></i>
                                                                В архів
                                                            </button>
                                                        </form>
                                                    @endif
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
                                    <i class="bi bi-ticket-perforated"></i>
                                </div>
                                <h3>Абонементів поки немає</h3>
                                <p>Додайте шаблон для цього типу занять, коли він буде потрібен.</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <div class="modal fade" id="editSubscriptionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" id="editSubscriptionForm">
                @csrf
                @method('PUT')

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Редагування абонементу</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editTitle" class="form-label">Назва</label>
                            <input type="text" class="form-control" id="editTitle" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="editType" class="form-label">Тип</label>
                            <select class="form-select" id="editType" name="type" required>
                                <option value="individual">Індивідуальний</option>
                                <option value="group">Груповий</option>
                                <option value="pair">Парний</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="editLessons" class="form-label">Занять на тиждень</label>
                            <input type="number" class="form-control" id="editLessons" name="lessons_per_week" min="1" max="7" required>
                        </div>

                        <div class="mb-3">
                            <label for="editPrice" class="form-label">Ціна (грн)</label>
                            <input type="number" class="form-control" id="editPrice" name="price" step="0.01" required>
                        </div>

                        <div class="form-check">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" class="form-check-input" id="editIsActive" name="is_active" value="1">
                            <label for="editIsActive" class="form-check-label">Активний шаблон</label>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="admin-btn-primary">Зберегти зміни</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('editSubscriptionModal');
            const editForm = document.getElementById('editSubscriptionForm');

            editModal?.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                editForm.setAttribute('action', button.getAttribute('data-update-url'));
                document.getElementById('editTitle').value = button.getAttribute('data-title');
                document.getElementById('editType').value = button.getAttribute('data-type');
                document.getElementById('editLessons').value = button.getAttribute('data-lessons');
                document.getElementById('editPrice').value = button.getAttribute('data-price');
                document.getElementById('editIsActive').checked = button.getAttribute('data-active') === '1';
            });
        });
    </script>
@endsection
