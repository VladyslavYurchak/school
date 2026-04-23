@csrf

<div class="row g-3">

    <div class="col-md-6">
        <label class="form-label">Назва секції</label>
        <input type="text"
               name="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $section->title ?? '') }}">
        @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
        <div class="form-text">
            Якщо не заповнювати — назва підставиться автоматично (Grammar / Reading / Listening)
        </div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Тип секції</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @php
                $sectionTypes = [
                    'grammar' => 'Grammar',
                    'reading' => 'Reading',
                    'listening' => 'Listening',
                ];
            @endphp

            @foreach($sectionTypes as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('type', $section->type ?? 'mixed') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-3">
        <label class="form-label">Порядок</label>
        <input type="number"
               name="sort_order"
               min="0"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $section->sort_order ?? 0) }}">
        @error('sort_order')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Короткий опис</label>
        <textarea name="description"
                  rows="3"
                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $section->description ?? '') }}</textarea>
        @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Інструкція для учня</label>
        <textarea name="instruction_text"
                  rows="4"
                  class="form-control @error('instruction_text') is-invalid @enderror">{{ old('instruction_text', $section->instruction_text ?? '') }}</textarea>
        @error('instruction_text')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Тип медіа</label>
        <select name="media_type" class="form-select @error('media_type') is-invalid @enderror">
            @php
                $mediaTypes = [
                    'none' => 'Без медіа',
                    'youtube' => 'YouTube (відео)',
                    'audio' => 'Audio (mp3 URL)',
                    'text' => 'Текст (для reading)',
                ];
            @endphp

            @foreach($mediaTypes as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('media_type', $section->media_type ?? 'none') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('media_type')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label class="form-label">Посилання на медіа</label>
        <input type="text"
               name="media_url"
               class="form-control @error('media_url') is-invalid @enderror"
               value="{{ old('media_url', $section->media_url ?? '') }}"
               placeholder="https://www.youtube.com/watch?v=...">
        @error('media_url')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-text">
            Для аудіювання з YouTube вставляй звичайне посилання на відео.
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Назва медіа</label>
        <input type="text"
               name="media_title"
               class="form-control @error('media_title') is-invalid @enderror"
               value="{{ old('media_title', $section->media_title ?? '') }}"
               placeholder="Наприклад: Listening Part 1">
        @error('media_title')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12 mt-2">
        <div class="form-check">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input"
                   type="checkbox"
                   name="is_active"
                   value="1"
                   id="is_active"
                @checked(old('is_active', $section->is_active ?? true))>
            <label class="form-check-label" for="is_active">
                Секція активна
            </label>
        </div>
    </div>

</div>

<div class="mt-3 d-flex flex-wrap gap-2">
    <button type="submit" class="btn btn-custom">Зберегти</button>

    <a href="{{ route('admin.testing.tests.sections.index', $test) }}"
       class="btn btn-outline-secondary">
        Скасувати
    </a>
</div>
