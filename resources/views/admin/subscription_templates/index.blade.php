@extends('admin.layouts.layout')

@section('content')
    <div class="container py-4">
        <h1>Абонементи</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <a href="{{ route('admin.subscription-templates.create') }}" class="btn btn-primary mb-3">Додати новий абонемент</a>

        {{-- Індивідуальні --}}
        <h3>Індивідуальні абонементи</h3>
        @if($individualTemplates->count())
            <table class="table table-bordered align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Назва</th>
                    <th>Занять/тиждень</th>
                    <th>Ціна</th>
                    <th>Опис</th>
                    <th>Створено</th>
                    <th>Оновлено</th>
                    <th>Дія</th>
                </tr>
                </thead>
                <tbody>
                @foreach($individualTemplates as $template)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $template->title }}</td>
                        <td>{{ $template->lessons_per_week }}</td>
                        <td>{{ number_format($template->price, 2, ',', ' ') }} грн</td>
                        <td>{{ $template->description }}</td>
                        <td>{{ $template->created_at->format('d.m.Y') }}</td>
                        <td>{{ $template->updated_at->format('d.m.Y') }}</td>
                        <td class="d-flex gap-2">
                            <button
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editSubscriptionModal"
                                data-id="{{ $template->id }}"
                                data-title="{{ $template->title }}"
                                data-type="{{ $template->type }}"
                                data-lessons="{{ $template->lessons_per_week }}"
                                data-price="{{ $template->price }}"
                                data-description="{{ $template->description }}"
                            >
                                Редагувати
                            </button>

                            <form action="{{ route('admin.subscription-templates.destroy', $template->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Ви впевнені, що хочете видалити цей абонемент?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Видалити</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p>Індивідуальні абонементи відсутні.</p>
        @endif

        {{-- Групові --}}
        <h3 class="mt-4">Групові абонементи</h3>
        @if($groupTemplates->count())
            <table class="table table-bordered align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Назва</th>
                    <th>Занять/тиждень</th>
                    <th>Ціна</th>
                    <th>Опис</th>
                    <th>Створено</th>
                    <th>Оновлено</th>
                    <th>Дія</th>
                </tr>
                </thead>
                <tbody>
                @foreach($groupTemplates as $template)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $template->title }}</td>
                        <td>{{ $template->lessons_per_week }}</td>
                        <td>{{ number_format($template->price, 2, ',', ' ') }} грн</td>
                        <td>{{ $template->description }}</td>
                        <td>{{ $template->created_at->format('d.m.Y') }}</td>
                        <td>{{ $template->updated_at->format('d.m.Y') }}</td>
                        <td class="d-flex gap-2">
                            <button
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editSubscriptionModal"
                                data-id="{{ $template->id }}"
                                data-title="{{ $template->title }}"
                                data-type="{{ $template->type }}"
                                data-lessons="{{ $template->lessons_per_week }}"
                                data-price="{{ $template->price }}"
                                data-description="{{ $template->description }}"
                            >
                                Редагувати
                            </button>

                            <form action="{{ route('admin.subscription-templates.destroy', $template->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Ви впевнені, що хочете видалити цей абонемент?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Видалити</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p>Групові абонементи відсутні.</p>
        @endif
    </div>

    {{-- 🛠 Modal --}}
    <div class="modal fade" id="editSubscriptionModal" tabindex="-1" aria-labelledby="editSubscriptionModalLabel" aria-hidden="true">
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

                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Опис</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Зберегти зміни</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- JS --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('editSubscriptionModal');
            const editForm = document.getElementById('editSubscriptionForm');

            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                const id = button.getAttribute('data-id');
                const title = button.getAttribute('data-title');
                const type = button.getAttribute('data-type');
                const lessons = button.getAttribute('data-lessons');
                const price = button.getAttribute('data-price');
                const description = button.getAttribute('data-description');

                editForm.setAttribute('action', `/admin/subscription-templates/${id}`);
                document.getElementById('editTitle').value = title;
                document.getElementById('editType').value = type;
                document.getElementById('editLessons').value = lessons;
                document.getElementById('editPrice').value = price;
                document.getElementById('editDescription').value = description || '';
            });
        });
    </script>
@endsection
