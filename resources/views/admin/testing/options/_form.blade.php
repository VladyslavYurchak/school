@csrf

<div class="row g-3">

    <div class="col-12">
        <label class="form-label">Текст варіанту</label>
        <textarea name="option_text"
                  rows="4"
                  class="form-control @error('option_text') is-invalid @enderror"
                  required>{{ old('option_text', $option->option_text ?? '') }}</textarea>
        @error('option_text')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Значення (необов’язково)</label>
        <input type="text"
               name="option_value"
               class="form-control @error('option_value') is-invalid @enderror"
               value="{{ old('option_value', $option->option_value ?? '') }}"
               placeholder="Наприклад: a, b, c">
        @error('option_value')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Бали для цього варіанту</label>
        <input type="number"
               step="0.01"
               name="points"
               class="form-control @error('points') is-invalid @enderror"
               value="{{ old('points', $option->points ?? '') }}"
               placeholder="Якщо пусто — спрацює логіка питання">
        @error('points')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">
            Якщо поле порожнє, система візьме стандартні бали питання.
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Порядок</label>
        <input type="number"
               min="0"
               name="sort_order"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $option->sort_order ?? 0) }}">
        @error('sort_order')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check mb-2">
            <input type="hidden" name="is_correct" value="0">
            <input class="form-check-input"
                   type="checkbox"
                   name="is_correct"
                   value="1"
                   id="is_correct"
                @checked(old('is_correct', $option->is_correct ?? false))>
            <label class="form-check-label" for="is_correct">
                Це правильний варіант
            </label>
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Пояснення (необов’язково)</label>
        <textarea name="explanation"
                  rows="3"
                  class="form-control @error('explanation') is-invalid @enderror">{{ old('explanation', $option->explanation ?? '') }}</textarea>
        @error('explanation')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="admin-form-actions mt-3">
    <button type="submit" class="admin-btn-primary">Зберегти</button>

    <a href="{{ route('admin.testing.questions.options.index', $question) }}"
       class="admin-btn-soft">
        Скасувати
    </a>
</div>
