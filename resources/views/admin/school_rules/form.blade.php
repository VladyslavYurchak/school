<form class="admin-panel admin-form admin-form-card"
      action="{{ isset($schoolRule) ? route('admin.school-rules.update', $schoolRule) : route('admin.school-rules.store') }}"
      method="POST">
    @csrf
    @if(isset($schoolRule))
        @method('PUT')
    @endif

    <div class="admin-panel-header">
        <h2 class="admin-panel-title">Дані правила</h2>
        <span class="admin-badge admin-badge-muted">
            {{ isset($schoolRule) ? 'Редагування' : 'Створення' }}
        </span>
    </div>

    <div class="admin-panel-body">
        <div class="admin-form-section">
            <label for="rule-title" class="admin-form-label">Назва правила</label>
            <input type="text"
                   id="rule-title"
                   name="title"
                   class="form-control @error('title') is-invalid @enderror"
                   required
                   value="{{ old('title', $schoolRule->title ?? '') }}">
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="admin-form-section">
            <label for="content-editor" class="admin-form-label">Текст правила</label>
            <textarea name="content"
                      id="content-editor"
                      class="form-control @error('content') is-invalid @enderror"
                      rows="10">{{ old('content', $schoolRule->content ?? '') }}</textarea>
            @error('content')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="admin-form-section h-100">
                    <label for="rule-sort-order" class="admin-form-label">Порядок</label>
                    <input type="number"
                           id="rule-sort-order"
                           name="sort_order"
                           class="form-control @error('sort_order') is-invalid @enderror"
                           min="0"
                           value="{{ old('sort_order', $schoolRule->sort_order ?? 0) }}">
                    @error('sort_order')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Менше число показується вище на сторінці правил.</div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="admin-form-section h-100">
                    <label for="rule-is-active" class="admin-form-label">Статус</label>
                    <select id="rule-is-active" name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                        <option value="1" @selected(old('is_active', $schoolRule->is_active ?? 1) == 1)>Активне, показувати на сайті</option>
                        <option value="0" @selected(old('is_active', $schoolRule->is_active ?? 1) == 0)>Приховане</option>
                    </select>
                    @error('is_active')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="admin-form-actions mt-3">
            <a href="{{ route('admin.school-rules.index') }}" class="admin-btn-soft">
                <i class="bi bi-x-lg"></i>
                Скасувати
            </a>

            <button type="submit" class="admin-btn-primary">
                <i class="bi bi-check2"></i>
                {{ isset($schoolRule) ? 'Оновити' : 'Зберегти' }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorElement = document.querySelector('#content-editor');

            if (editorElement) {
                ClassicEditor
                    .create(editorElement, {
                        toolbar: [
                            'heading',
                            '|',
                            'bold',
                            'italic',
                            'link',
                            'bulletedList',
                            'numberedList',
                            '|',
                            'blockQuote',
                            'undo',
                            'redo'
                        ]
                    })
                    .catch(error => {
                        console.error(error);
                    });
            }
        });
    </script>
@endpush
