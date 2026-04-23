@csrf

<div class="row g-3">

    <div class="col-md-4">
        <label class="form-label">Секція</label>
        <select name="section_id" class="form-select @error('section_id') is-invalid @enderror" required>
            <option value="">Оберіть секцію</option>
            @foreach($sections as $sectionItem)
                <option value="{{ $sectionItem->id }}"
                    @selected((string) old('section_id', $question->section_id ?? '') === (string) $sectionItem->id)>
                    {{ $sectionItem->title }}
                </option>
            @endforeach
        </select>
        @error('section_id')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Тип питання</label>
        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
            @php
                $questionTypes = [
                    'single_choice' => 'Single choice',
                    'multiple_choice' => 'Multiple choice',
                    'short_text' => 'Short text',
                    'long_text' => 'Long text',
                    'true_false' => 'True / False',
                ];
            @endphp

            @foreach($questionTypes as $value => $label)
                <option value="{{ $value }}"
                    @selected(old('type', $question->type ?? 'single_choice') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('type')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Рівень складності</label>
        <select name="difficulty_level" class="form-select @error('difficulty_level') is-invalid @enderror" required>
            <option value="">Оберіть рівень</option>
            @foreach(['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level)
                <option value="{{ $level }}"
                    @selected(old('difficulty_level', $question->difficulty_level ?? '') === $level)>
                    {{ $level }}
                </option>
            @endforeach
        </select>
        @error('difficulty_level')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Заголовок питання</label>
        <input type="text"
               name="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $question->title ?? '') }}"
               placeholder="Необов'язково">
        @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Текст питання</label>
        <textarea name="question_text"
                  rows="4"
                  class="form-control @error('question_text') is-invalid @enderror"
                  required>{{ old('question_text', $question->question_text ?? '') }}</textarea>
        @error('question_text')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Пояснення / helper text</label>
        <textarea name="helper_text"
                  rows="3"
                  class="form-control @error('helper_text') is-invalid @enderror">{{ old('helper_text', $question->helper_text ?? '') }}</textarea>
        @error('helper_text')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Контент перед питанням</label>
        <textarea name="content_before"
                  rows="3"
                  class="form-control @error('content_before') is-invalid @enderror">{{ old('content_before', $question->content_before ?? '') }}</textarea>
        @error('content_before')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Контент після питання</label>
        <textarea name="content_after"
                  rows="3"
                  class="form-control @error('content_after') is-invalid @enderror">{{ old('content_after', $question->content_after ?? '') }}</textarea>
        @error('content_after')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Бал за правильну відповідь</label>
        <input type="number"
               step="0.01"
               min="0"
               name="default_correct_points"
               class="form-control @error('default_correct_points') is-invalid @enderror"
               value="{{ old('default_correct_points', $question->default_correct_points ?? 1) }}"
               required>
        @error('default_correct_points')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Бал за неправильну відповідь</label>
        <input type="number"
               step="0.01"
               name="default_incorrect_points"
               class="form-control @error('default_incorrect_points') is-invalid @enderror"
               value="{{ old('default_incorrect_points', $question->default_incorrect_points ?? 0) }}">
        @error('default_incorrect_points')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">Порядок</label>
        <input type="number"
               min="0"
               name="sort_order"
               class="form-control @error('sort_order') is-invalid @enderror"
               value="{{ old('sort_order', $question->sort_order ?? 0) }}">
        @error('sort_order')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check">
                <input type="hidden" name="is_required" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_required"
                       value="1"
                       id="is_required"
                    @checked(old('is_required', $question->is_required ?? true))>
                <label class="form-check-label" for="is_required">
                    Обов’язкове
                </label>
            </div>

            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input"
                       type="checkbox"
                       name="is_active"
                       value="1"
                       id="is_active"
                    @checked(old('is_active', $question->is_active ?? true))>
                <label class="form-check-label" for="is_active">
                    Активне
                </label>
            </div>
        </div>
    </div>

</div>

<div class="mt-3 d-flex flex-wrap gap-2">
    <button type="submit" class="btn btn-custom">Зберегти</button>

    <a href="{{ route('admin.testing.tests.questions.index', $test) }}"
       class="btn btn-outline-secondary">
        Скасувати
    </a>
</div>
