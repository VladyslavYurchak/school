@csrf
<div class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Назва</label>
        <input type="text"
               name="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $test->title ?? '') }}"
               required>
        @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Slug</label>
        <input type="text"
               name="slug"
               class="form-control @error('slug') is-invalid @enderror"
               value="{{ old('slug', $test->slug ?? '') }}"
               required>
        @error('slug')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Мова</label>
        <select name="language_code" class="form-select @error('language_code') is-invalid @enderror" required>
            @foreach(['en' => 'English', 'fr' => 'French', 'zh' => 'Chinese'] as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('language_code', $test->language_code ?? '') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('language_code')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    @if(isset($test))
        <div class="col-md-4">
            <label class="form-label">Максимальний бал</label>
            <input type="text"
                   class="form-control"
                   value="{{ number_format((float) ($test->max_score ?? 0), 2, '.', '') }}"
                   readonly>
            <div class="form-text">
                Розраховується автоматично на основі питань і варіантів відповідей.
            </div>
        </div>
    @endif

    <div class="col-md-6">
        <label class="form-label">Ліміт часу, хв</label>
        <input type="number"
               min="1"
               name="time_limit_minutes"
               class="form-control @error('time_limit_minutes') is-invalid @enderror"
               value="{{ old('time_limit_minutes', $test->time_limit_minutes ?? '') }}">
        @error('time_limit_minutes')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Порядок</label>
        <input type="number"
               min="0"
               name="sort_order"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $test->sort_order ?? 0) }}">
        @error('sort_order')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Опис</label>
        <textarea name="description"
                  class="form-control @error('description') is-invalid @enderror"
                  rows="3">{{ old('description', $test->description ?? '') }}</textarea>
        @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Вступний текст</label>
        <textarea name="intro_text"
                  class="form-control @error('intro_text') is-invalid @enderror"
                  rows="4">{{ old('intro_text', $test->intro_text ?? '') }}</textarea>
        @error('intro_text')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_active"
                       value="1"
                       id="is_active"
                    @checked(old('is_active', $test->is_active ?? true))>
                <label class="form-check-label" for="is_active">Активний</label>
            </div>

            <div class="form-check">
                <input type="hidden" name="is_public" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_public"
                       value="1"
                       id="is_public"
                    @checked(old('is_public', $test->is_public ?? false))>
                <label class="form-check-label" for="is_public">Публічний</label>
            </div>

            <div class="form-check">
                <input type="hidden" name="randomize_questions" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       name="randomize_questions"
                       value="1"
                       id="randomize_questions"
                    @checked(old('randomize_questions', $test->randomize_questions ?? false))>
                <label class="form-check-label" for="randomize_questions">Перемішувати питання</label>
            </div>

            <div class="form-check">
                <input type="hidden" name="show_result_immediately" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       name="show_result_immediately"
                       value="1"
                       id="show_result_immediately"
                    @checked(old('show_result_immediately', $test->show_result_immediately ?? true))>
                <label class="form-check-label" for="show_result_immediately">Показувати результат одразу</label>
            </div>
        </div>
    </div>

</div>

<div class="admin-form-actions mt-3">
    <button type="submit" class="admin-btn-primary">Зберегти</button>

    <a href="{{ route('admin.testing.tests.index') }}" class="admin-btn-soft">
        Скасувати
    </a>
</div>
