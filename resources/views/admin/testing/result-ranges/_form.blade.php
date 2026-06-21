@csrf

<div class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Назва результату</label>
        <input type="text"
               name="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $resultRange->title ?? '') }}"
               placeholder="Наприклад: Хороший результат"
               required>
        @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Рівень CEFR</label>
        <select name="level_code" class="form-select @error('level_code') is-invalid @enderror">
            <option value="">Без прив’язки</option>
            @foreach(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level)
                <option value="{{ $level }}"
                    @selected(old('level_code', $resultRange->level_code ?? '') === $level)>
                    {{ $level }}
                </option>
            @endforeach
        </select>
        @error('level_code')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">
            Необов’язково. Основний рівень все одно визначається за питаннями різної складності.
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label">Мінімальний бал зі 100</label>
        <input type="number"
               step="0.01"
               min="0"
               max="100"
               name="min_score"
               class="form-control @error('min_score') is-invalid @enderror"
               value="{{ old('min_score', $resultRange->min_score ?? '') }}"
               required>
        @error('min_score')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6">
        <label class="form-label">Максимальний бал зі 100</label>
        <input type="number"
               step="0.01"
               min="0"
               max="100"
               name="max_score"
               class="form-control @error('max_score') is-invalid @enderror"
               value="{{ old('max_score', $resultRange->max_score ?? '') }}"
               required>
        @error('max_score')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Опис результату</label>
        <textarea name="description"
                  rows="4"
                  class="form-control @error('description') is-invalid @enderror"
                  placeholder="Наприклад: Ви добре впоралися із завданням.">{{ old('description', $resultRange->description ?? '') }}</textarea>
        @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Рекомендація</label>
        <textarea name="recommendation_text"
                  rows="4"
                  class="form-control @error('recommendation_text') is-invalid @enderror"
                  placeholder="Наприклад: Заповніть анкету, і ми підберемо для вас відповідну програму навчання.">{{ old('recommendation_text', $resultRange->recommendation_text ?? '') }}</textarea>
        @error('recommendation_text')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="admin-form-actions mt-3">
    <button type="submit" class="admin-btn-primary">Зберегти</button>

    <a href="{{ route('admin.testing.tests.result-ranges.index', $test) }}"
       class="admin-btn-soft">
        Скасувати
    </a>
</div>
