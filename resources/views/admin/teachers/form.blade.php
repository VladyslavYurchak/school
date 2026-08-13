<form action="{{ isset($teacher) ? route('admin.teachers.update', $teacher->id) : route('admin.teachers.store') }}"
      method="POST"
      enctype="multipart/form-data"
      class="admin-panel admin-form admin-form-card teacher-profile-form">
    @csrf
    @if(isset($teacher))
        @method('PUT')
    @endif

    <div class="admin-panel-header">
        <h2 class="admin-panel-title">Дані викладача</h2>
    </div>

    <div class="admin-panel-body">
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center gap-2" role="status">
                <i class="bi bi-check-circle-fill"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger" role="alert">
                <strong>Не вдалося зберегти:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Ім'я</label>
                <input type="text" name="first_name" class="form-control" required value="{{ old('first_name', $teacher->first_name ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Прізвище</label>
                <input type="text" name="last_name" class="form-control" required value="{{ old('last_name', $teacher->last_name ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Телефон</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $teacher->phone ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Користувач</label>
                <select name="user_id" class="form-select" required @if(isset($teacher)) disabled @endif>
                    <option value="">Оберіть користувача</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected(old('user_id', $teacher->user_id ?? '') == $user->id)>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>

                @if(isset($teacher))
                    <input type="hidden" name="user_id" value="{{ $teacher->user_id }}">
                @endif
            </div>

            <div class="col-md-6">
                <label class="form-label">Ціна індивідуального заняття</label>
                <input type="number" name="lesson_price" class="form-control" step="0.01" value="{{ old('lesson_price', $teacher->lesson_price ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ціна групового заняття</label>
                <input type="number" name="group_lesson_price" class="form-control" step="0.01" value="{{ old('group_lesson_price', $teacher->group_lesson_price ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ціна парного заняття</label>
                <input type="number" name="pair_lesson_price" class="form-control" step="0.01" value="{{ old('pair_lesson_price', $teacher->pair_lesson_price ?? '') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Ціна пробного заняття</label>
                <input type="number" name="trial_lesson_price" class="form-control" step="0.01" value="{{ old('trial_lesson_price', $teacher->trial_lesson_price ?? '') }}">
            </div>

            <div class="col-md-3">
                <label class="form-label">Активний</label>
                <select name="is_active" class="form-select">
                    <option value="1" @selected(old('is_active', $teacher->is_active ?? 1) == 1)>Так</option>
                    <option value="0" @selected(old('is_active', $teacher->is_active ?? 1) == 0)>Ні</option>
                </select>
            </div>

            <div class="col-md-12">
                <label class="form-label">Нотатки</label>
                <textarea name="note" class="form-control" rows="3">{{ old('note', $teacher->note ?? '') }}</textarea>
            </div>

            <div class="col-12 mt-4">
                <hr>
                <h5 class="mb-3">Для сторінки "Наші вчителі"</h5>
            </div>

            <div class="col-md-4">
                <label class="form-label">Фото для сайту</label>
                <input type="file"
                       name="public_photo"
                       id="teacher-public-photo"
                       class="form-control @error('public_photo') is-invalid @enderror"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                       aria-describedby="teacher-photo-help teacher-photo-error">
                <div id="teacher-photo-help" class="form-text">JPG, PNG або WebP, до 4 МБ. Нове фото замінить поточне.</div>
                <div id="teacher-photo-error" class="invalid-feedback @unless($errors->has('public_photo')) d-none @endunless">
                    @error('public_photo'){{ $message }}@enderror
                </div>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <div id="teacher-photo-preview-wrap" class="teacher-photo-edit-preview @if(empty($teacher?->public_photo)) d-none @endif">
                    <img id="teacher-photo-preview"
                         @if(!empty($teacher?->public_photo)) src="{{ asset('storage/' . $teacher->public_photo) }}" @endif
                         alt="Попередній перегляд фото">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Порядок відображення</label>
                <input type="number" name="public_sort_order" class="form-control" min="0" value="{{ old('public_sort_order', $teacher->public_sort_order ?? 0) }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Посада біля імені</label>
                <input type="text"
                       name="public_position"
                       class="form-control"
                       value="{{ old('public_position', $teacher->public_position ?? '') }}"
                       placeholder="Наприклад: Викладачка англійської мови🇬🇧">
            </div>

            <div class="col-md-12">
                <label for="teacher-public-bio-editor" class="form-label">Опис викладача</label>
                <textarea name="public_bio"
                          id="teacher-public-bio-editor"
                          class="form-control"
                          rows="9">{{ old('public_bio', $teacher->public_bio ?? '') }}</textarea>
                <div class="form-text">Можна робити абзаци, курсив, жирний текст і списки.</div>
            </div>

            <div class="col-md-12">
                <label class="form-label">Факти під фото</label>
                <textarea name="public_details"
                          class="form-control"
                          rows="5"
                          placeholder="Досвід: 6 років&#10;Формат: онлайн/офлайн&#10;Заняття: індивідуально/в групі&#10;Учні: діти від 8 років/дорослі">{{ old('public_details', $teacher->public_details ?? '') }}</textarea>
                <div class="form-text">Кожен рядок буде показаний під фото окремим рядком.</div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Показувати на сайті</label>
                <select name="is_public" class="form-select">
                    <option value="1" @selected(old('is_public', $teacher->is_public ?? 0) == 1)>Так</option>
                    <option value="0" @selected(old('is_public', $teacher->is_public ?? 0) == 0)>Ні</option>
                </select>
            </div>

            <div class="col-12">
                <div class="admin-form-actions teacher-profile-actions">
                    <a href="{{ route('admin.teachers.index') }}" class="admin-btn-soft">Скасувати</a>
                    <button type="submit" class="admin-btn-primary" id="teacher-profile-submit">
                        <i class="bi bi-check-lg"></i>
                        {{ isset($teacher) ? 'Оновити' : 'Зберегти' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bioEditor = document.querySelector('#teacher-public-bio-editor');
            const form = document.querySelector('.teacher-profile-form');
            const photoInput = document.querySelector('#teacher-public-photo');
            const photoPreview = document.querySelector('#teacher-photo-preview');
            const photoPreviewWrap = document.querySelector('#teacher-photo-preview-wrap');
            const photoError = document.querySelector('#teacher-photo-error');
            const submitButton = document.querySelector('#teacher-profile-submit');

            photoInput?.addEventListener('change', function () {
                const file = this.files?.[0];

                photoError?.classList.add('d-none');
                this.classList.remove('is-invalid');

                if (!file) {
                    return;
                }

                if (file.size > 4 * 1024 * 1024) {
                    this.value = '';
                    this.classList.add('is-invalid');
                    photoError.textContent = 'Фото завелике. Оберіть файл до 4 МБ.';
                    photoError.classList.remove('d-none');
                    return;
                }

                photoPreview.src = URL.createObjectURL(file);
                photoPreviewWrap.classList.remove('d-none');
            });

            form?.addEventListener('submit', function () {
                if (!submitButton) {
                    return;
                }

                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Зберігаємо...';
            });

            if (bioEditor) {
                ClassicEditor
                    .create(bioEditor, {
                        toolbar: [
                            'bold',
                            'italic',
                            '|',
                            'bulletedList',
                            'numberedList',
                            'blockQuote',
                            '|',
                            'undo',
                            'redo'
                        ]
                    })
                    .catch(error => console.error(error));
            }
        });
    </script>
@endpush
