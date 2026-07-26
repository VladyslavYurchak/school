@php
    $item = $link->vocabularyItem ?? null;
    $partsOfSpeech = [
        '' => 'Не вказано',
        'noun' => 'Іменник',
        'verb' => 'Дієслово',
        'adjective' => 'Прикметник',
        'adverb' => 'Прислівник',
        'pronoun' => 'Займенник',
        'preposition' => 'Прийменник',
        'phrase' => 'Фраза',
        'other' => 'Інше',
    ];
@endphp

<form method="POST"
      action="{{ isset($link)
          ? route('admin.course.lesson.vocabulary.update', [$lesson, $link])
          : route('admin.course.lesson.vocabulary.store', $lesson) }}"
      class="admin-panel admin-form admin-form-card">
    @csrf
    @isset($link) @method('PUT') @endisset

    <div class="admin-panel-header">
        <h2 class="admin-panel-title">Словниковий запис</h2>
        <span class="admin-badge admin-badge-muted">{{ $lesson->course->language->name }}</span>
    </div>

    <div class="admin-panel-body">
        @isset($link)
            <div class="alert alert-warning">
                Основні дані слова є спільними. Їх зміна оновить запис у всіх уроках, де він використовується.
            </div>
        @endisset

        <div class="row g-3">
            <div class="col-md-6">
                <label for="vocabulary-term" class="admin-form-label">Слово або фраза</label>
                <input type="text" id="vocabulary-term" name="term" required maxlength="255"
                       class="form-control @error('term') is-invalid @enderror"
                       value="{{ old('term', $item->term ?? '') }}">
                @error('term')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="vocabulary-translation" class="admin-form-label">Основний переклад</label>
                <input type="text" id="vocabulary-translation" name="translation" required
                       class="form-control @error('translation') is-invalid @enderror"
                       value="{{ old('translation', $item->translation ?? '') }}">
                @error('translation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="vocabulary-transcription" class="admin-form-label">Транскрипція</label>
                <input type="text" id="vocabulary-transcription" name="transcription" maxlength="255"
                       class="form-control @error('transcription') is-invalid @enderror"
                       placeholder="Наприклад: /ˈtʃælɪndʒ/"
                       value="{{ old('transcription', $item->transcription ?? '') }}">
                @error('transcription')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="vocabulary-part-of-speech" class="admin-form-label">Частина мови</label>
                <select id="vocabulary-part-of-speech" name="part_of_speech" class="form-select">
                    @foreach($partsOfSpeech as $value => $label)
                        <option value="{{ $value }}" @selected(old('part_of_speech', $item->part_of_speech ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label for="vocabulary-explanation" class="admin-form-label">Додаткові значення або пояснення</label>
                <textarea id="vocabulary-explanation" name="explanation" rows="3"
                          class="form-control @error('explanation') is-invalid @enderror">{{ old('explanation', $item->explanation ?? '') }}</textarea>
                @error('explanation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="vocabulary-example" class="admin-form-label">Приклад</label>
                <textarea id="vocabulary-example" name="example" rows="3"
                          class="form-control @error('example') is-invalid @enderror">{{ old('example', $item->example ?? '') }}</textarea>
                @error('example')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="vocabulary-example-translation" class="admin-form-label">Переклад прикладу</label>
                <textarea id="vocabulary-example-translation" name="example_translation" rows="3"
                          class="form-control @error('example_translation') is-invalid @enderror">{{ old('example_translation', $item->example_translation ?? '') }}</textarea>
                @error('example_translation')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="vocabulary-note" class="admin-form-label">Нотатка саме для цього уроку</label>
                <textarea id="vocabulary-note" name="note" rows="2"
                          class="form-control @error('note') is-invalid @enderror">{{ old('note', $link->note ?? '') }}</textarea>
                @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <input type="hidden" name="is_required" value="0">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="vocabulary-is-required"
                           name="is_required" value="1" @checked(old('is_required', $link->is_required ?? false))>
                    <label class="form-check-label" for="vocabulary-is-required">Обов’язкове для вивчення</label>
                </div>
            </div>
        </div>

        <div class="admin-form-actions mt-4">
            <a href="{{ route('admin.course.lesson.vocabulary.index', $lesson) }}" class="admin-btn-soft">
                <i class="bi bi-x-lg"></i> Скасувати
            </a>
            <button type="submit" class="admin-btn-primary">
                <i class="bi bi-check2"></i> {{ isset($link) ? 'Оновити' : 'Додати до уроку' }}
            </button>
        </div>
    </div>
</form>
