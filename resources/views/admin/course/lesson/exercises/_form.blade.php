@php
    use App\Models\LessonExercise;

    $exerciseType = old('type', $exercise->type ?? $type ?? LessonExercise::TYPE_MATCHING);
    $isFillBlank = $exerciseType === LessonExercise::TYPE_FILL_BLANK;
    $isWordOrder = $exerciseType === LessonExercise::TYPE_WORD_ORDER;
    $isTransformation = $exerciseType === LessonExercise::TYPE_TRANSFORMATION;
    $isTrueFalse = $exerciseType === LessonExercise::TYPE_TRUE_FALSE;
    $isDictation = $exerciseType === LessonExercise::TYPE_DICTATION;
    $singleMinimum = $isWordOrder || $isTransformation || $isTrueFalse || $isDictation;

    $savedPairs = isset($exercise)
        ? $exercise->items->map(function ($item) {
            $acceptedAnswers = collect(data_get($item->settings, 'accepted_answers', []))
                ->reject(fn ($answer) => mb_strtolower(trim((string) $answer)) === mb_strtolower(trim($item->answer)))
                ->implode(PHP_EOL);

            return [
                'prompt' => $item->prompt,
                'answer' => $item->answer,
                'alternatives_text' => $acceptedAnswers,
                'explanation' => data_get($item->settings, 'explanation'),
                'existing_item_id' => $item->id,
                'audio_path' => $item->audio_path,
            ];
        })->all()
        : array_fill(0, $singleMinimum ? 1 : 2, [
            'prompt' => '',
            'answer' => '',
            'alternatives_text' => '',
            'explanation' => '',
            'existing_item_id' => null,
            'audio_path' => null,
        ]);

    $pairs = old('pairs', $savedPairs);
    $answerMode = old('answer_mode', data_get($exercise->settings ?? [], 'answer_mode', 'typing'));

    $typeTitle = match ($exerciseType) {
        LessonExercise::TYPE_FILL_BLANK => 'Заповнити пропущені слова',
        LessonExercise::TYPE_WORD_ORDER => 'Скласти речення зі слів',
        LessonExercise::TYPE_TRANSFORMATION => 'Трансформація речення',
        LessonExercise::TYPE_TRUE_FALSE => 'Правда чи неправда',
        LessonExercise::TYPE_DICTATION => 'Диктант',
        default => 'З’єднати слово з відповіддю',
    };

    $titlePlaceholder = match ($exerciseType) {
        LessonExercise::TYPE_FILL_BLANK => 'Наприклад: Встав правильну форму дієслова',
        LessonExercise::TYPE_WORD_ORDER => 'Наприклад: Склади правильні речення',
        LessonExercise::TYPE_TRANSFORMATION => 'Наприклад: Зроби речення заперечним',
        LessonExercise::TYPE_TRUE_FALSE => 'Наприклад: Перевір розуміння тексту',
        LessonExercise::TYPE_DICTATION => 'Наприклад: Запиши почуті речення',
        default => 'Наприклад: З’єднай слова з перекладом',
    };

    $descriptionPlaceholder = match ($exerciseType) {
        LessonExercise::TYPE_FILL_BLANK => 'Заповни пропуски та натисни «Перевірити».',
        LessonExercise::TYPE_WORD_ORDER => 'Натискай слова у правильному порядку.',
        LessonExercise::TYPE_TRANSFORMATION => 'Перетвори кожне речення за вказаною умовою.',
        LessonExercise::TYPE_TRUE_FALSE => 'Прочитай твердження та обери «Правда» або «Неправда».',
        LessonExercise::TYPE_DICTATION => 'Прослухай аудіо та запиши почуте.',
        default => 'Обери слово зліва, а потім його переклад справа.',
    };
@endphp

<form method="POST"
      enctype="multipart/form-data"
      action="{{ isset($exercise)
          ? route('admin.course.lesson.exercises.update', [$lesson, $exercise])
          : route('admin.course.lesson.exercises.store', $lesson) }}"
      class="admin-panel admin-form admin-form-card">
    @csrf
    @isset($exercise) @method('PUT') @endisset

    <input type="hidden" name="type" value="{{ $exerciseType }}">

    <div class="admin-panel-header">
        <h2 class="admin-panel-title">{{ $typeTitle }}</h2>
        <span class="admin-badge admin-badge-muted">{{ $singleMinimum ? '1–30' : '2–30' }} завдань</span>
    </div>

    <div class="admin-panel-body">
        <div class="row g-3">
            <div class="col-12">
                <label for="exercise-title" class="admin-form-label">Назва вправи</label>
                <input type="text" id="exercise-title" name="title" required maxlength="255"
                       class="form-control @error('title') is-invalid @enderror"
                       placeholder="{{ $titlePlaceholder }}"
                       value="{{ old('title', $exercise->title ?? '') }}">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label for="exercise-description" class="admin-form-label">Інструкція для учня</label>
                <textarea id="exercise-description" name="description" rows="3" maxlength="3000"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="{{ $descriptionPlaceholder }}">{{ old('description', $exercise->description ?? '') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            @if($isFillBlank)
                <div class="col-12">
                    <span class="admin-form-label d-block">Як учень відповідатиме</span>
                    <div class="lesson-exercise-mode-options">
                        <label class="lesson-exercise-mode-option">
                            <input type="radio" name="answer_mode" value="typing" @checked($answerMode === 'typing')>
                            <span>
                                <strong>Введення відповіді</strong>
                                <small>Учень самостійно вписує слово. Регістр не враховується.</small>
                            </span>
                        </label>
                        <label class="lesson-exercise-mode-option">
                            <input type="radio" name="answer_mode" value="choice" @checked($answerMode === 'choice')>
                            <span>
                                <strong>Вибір зі списку</strong>
                                <small>Варіантами стануть усі правильні відповіді цієї вправи.</small>
                            </span>
                        </label>
                    </div>
                    @error('answer_mode')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            @endif
        </div>

        <div class="lesson-exercise-pairs-heading">
            <div>
                <h3>{{ $isTrueFalse ? 'Твердження' : ($isDictation ? 'Аудіозавдання' : ($isWordOrder || $isTransformation ? 'Речення' : ($isFillBlank ? 'Речення і відповіді' : 'Пари'))) }}</h3>
                <p>
                    @if($isFillBlank)
                        Позначте місце пропуску трьома підкресленнями: <strong>___</strong>.
                    @elseif($isWordOrder)
                        Напишіть речення у правильному порядку. Слова перемішаються автоматично.
                    @elseif($isTransformation)
                        Додаткові правильні варіанти пишіть з нового рядка.
                    @elseif($isTrueFalse)
                        Пояснення учень побачить після перевірки відповіді.
                    @elseif($isDictation)
                        Кожне завдання має окреме аудіо до 12 МБ. Додаткові відповіді пишіть з нового рядка.
                    @else
                        Однакові слова чи відповіді в межах вправи не допускаються.
                    @endif
                </p>
            </div>
            <button type="button" id="add-exercise-pair" class="admin-btn-soft">
                <i class="bi bi-plus-lg"></i> Додати завдання
            </button>
        </div>

        @error('pairs')<div class="alert alert-danger">{{ $message }}</div>@enderror

        <div id="exercise-pairs" class="lesson-exercise-pairs" data-min-pairs="{{ $singleMinimum ? 1 : 2 }}">
            @foreach($pairs as $index => $pair)
                <div class="lesson-exercise-pair">
                    <span class="lesson-exercise-pair-number">{{ $index + 1 }}</span>
                    <div class="lesson-exercise-pair-fields">
                        @if(!empty($pair['existing_item_id']))
                            <input type="hidden" data-field="existing_item_id"
                                   name="pairs[{{ $index }}][existing_item_id]"
                                   value="{{ $pair['existing_item_id'] }}">
                        @endif

                        @if($isTrueFalse)
                            <div>
                                <label class="admin-form-label">Твердження</label>
                                <input type="text" data-field="prompt" name="pairs[{{ $index }}][prompt]"
                                       required maxlength="255" class="form-control @error("pairs.$index.prompt") is-invalid @enderror"
                                       placeholder="Daniel has only one class."
                                       value="{{ $pair['prompt'] ?? '' }}">
                                @error("pairs.$index.prompt")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="admin-form-label">Правильна відповідь</label>
                                <select data-field="answer" name="pairs[{{ $index }}][answer]" required
                                        class="form-select @error("pairs.$index.answer") is-invalid @enderror">
                                    <option value="">Оберіть...</option>
                                    <option value="true" @selected(($pair['answer'] ?? '') === 'true')>Правда</option>
                                    <option value="false" @selected(($pair['answer'] ?? '') === 'false')>Неправда</option>
                                </select>
                                @error("pairs.$index.answer")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="lesson-exercise-field-wide">
                                <label class="admin-form-label">Пояснення після перевірки (необов’язково)</label>
                                <textarea data-field="explanation" name="pairs[{{ $index }}][explanation]"
                                          rows="2" maxlength="1000" class="form-control"
                                          placeholder="У тексті сказано, що Daniel має дві пари.">{{ $pair['explanation'] ?? '' }}</textarea>
                            </div>
                        @else
                            @if($isDictation)
                                <div class="lesson-exercise-field-wide">
                                    <label class="admin-form-label">Аудіофайл</label>
                                    @if(!empty($pair['audio_path']))
                                        <audio controls preload="none" class="lesson-exercise-admin-audio">
                                            <source src="{{ Storage::url($pair['audio_path']) }}">
                                        </audio>
                                        <small class="text-muted d-block mb-2">Новий файл замінить поточний.</small>
                                    @endif
                                    <input type="file" data-field="audio" name="pairs[{{ $index }}][audio]"
                                           accept=".mp3,.wav,.m4a,.ogg,.webm,audio/*"
                                           class="form-control @error("pairs.$index.audio") is-invalid @enderror"
                                           @required(empty($pair['audio_path']))>
                                    @error("pairs.$index.audio")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            <div>
                                <label class="admin-form-label">
                                    {{ match (true) {
                                        $isWordOrder => 'Підказка або переклад (необов’язково)',
                                        $isTransformation => 'Початкове речення та умова',
                                        $isDictation => 'Підказка (необов’язково)',
                                        $isFillBlank => 'Речення з ___',
                                        default => 'Слово або фраза',
                                    } }}
                                </label>
                                <input type="text" data-field="prompt" name="pairs[{{ $index }}][prompt]"
                                       @required(!$isWordOrder && !$isDictation) maxlength="255"
                                       class="form-control @error("pairs.$index.prompt") is-invalid @enderror"
                                       placeholder="{{ match (true) {
                                           $isWordOrder => 'Я щодня ходжу до школи.',
                                           $isTransformation => 'She works here. → Зробіть заперечення.',
                                           $isDictation => 'Наприклад: речення про подорож',
                                           $isFillBlank => 'She ___ to school every day.',
                                           default => '',
                                       } }}"
                                       value="{{ $pair['prompt'] ?? '' }}">
                                @error("pairs.$index.prompt")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div>
                                <label class="admin-form-label">
                                    {{ $isWordOrder ? 'Правильне речення' : ($isFillBlank ? 'Правильне слово' : ($isDictation ? 'Точний текст аудіо' : ($isTransformation ? 'Основна правильна відповідь' : 'Відповідь або переклад'))) }}
                                </label>
                                <input type="text" data-field="answer" name="pairs[{{ $index }}][answer]"
                                       required maxlength="255"
                                       class="form-control @error("pairs.$index.answer") is-invalid @enderror"
                                       placeholder="{{ $isWordOrder ? 'I go to school every day.' : ($isFillBlank ? 'goes' : ($isTransformation ? 'She does not work here.' : '')) }}"
                                       value="{{ $pair['answer'] ?? '' }}">
                                @error("pairs.$index.answer")<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if($isTransformation || $isDictation)
                                <div class="lesson-exercise-field-wide">
                                    <label class="admin-form-label">Інші допустимі відповіді (необов’язково)</label>
                                    <textarea data-field="alternatives_text"
                                              name="pairs[{{ $index }}][alternatives_text]"
                                              rows="2" maxlength="2000" class="form-control"
                                              placeholder="Кожен варіант з нового рядка">{{ $pair['alternatives_text'] ?? '' }}</textarea>
                                </div>
                            @endif
                        @endif
                    </div>
                    <button type="button" class="lesson-exercise-remove-pair admin-btn-danger"
                            title="Видалити завдання" aria-label="Видалити завдання">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @endforeach
        </div>

        <template id="exercise-pair-template">
            <div class="lesson-exercise-pair">
                <span class="lesson-exercise-pair-number"></span>
                <div class="lesson-exercise-pair-fields">
                    @if($isTrueFalse)
                        <div>
                            <label class="admin-form-label">Твердження</label>
                            <input type="text" data-field="prompt" required maxlength="255" class="form-control">
                        </div>
                        <div>
                            <label class="admin-form-label">Правильна відповідь</label>
                            <select data-field="answer" required class="form-select">
                                <option value="">Оберіть...</option>
                                <option value="true">Правда</option>
                                <option value="false">Неправда</option>
                            </select>
                        </div>
                        <div class="lesson-exercise-field-wide">
                            <label class="admin-form-label">Пояснення після перевірки (необов’язково)</label>
                            <textarea data-field="explanation" rows="2" maxlength="1000" class="form-control"></textarea>
                        </div>
                    @else
                        @if($isDictation)
                            <div class="lesson-exercise-field-wide">
                                <label class="admin-form-label">Аудіофайл</label>
                                <input type="file" data-field="audio" required
                                       accept=".mp3,.wav,.m4a,.ogg,.webm,audio/*" class="form-control">
                            </div>
                        @endif
                        <div>
                            <label class="admin-form-label">
                                {{ $isTransformation ? 'Початкове речення та умова' : ($isDictation ? 'Підказка (необов’язково)' : ($isWordOrder ? 'Підказка або переклад (необов’язково)' : ($isFillBlank ? 'Речення з ___' : 'Слово або фраза'))) }}
                            </label>
                            <input type="text" data-field="prompt" @required(!$isWordOrder && !$isDictation)
                                   maxlength="255"
                                   class="form-control">
                        </div>
                        <div>
                            <label class="admin-form-label">
                                {{ $isWordOrder ? 'Правильне речення' : ($isFillBlank ? 'Правильне слово' : ($isDictation ? 'Точний текст аудіо' : ($isTransformation ? 'Основна правильна відповідь' : 'Відповідь або переклад'))) }}
                            </label>
                            <input type="text" data-field="answer" required maxlength="255" class="form-control">
                        </div>
                        @if($isTransformation || $isDictation)
                            <div class="lesson-exercise-field-wide">
                                <label class="admin-form-label">Інші допустимі відповіді (необов’язково)</label>
                                <textarea data-field="alternatives_text" rows="2" maxlength="2000" class="form-control"></textarea>
                            </div>
                        @endif
                    @endif
                </div>
                <button type="button" class="lesson-exercise-remove-pair admin-btn-danger"
                        title="Видалити завдання" aria-label="Видалити завдання">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </template>

        <div class="mt-3">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="exercise-is-active"
                       name="is_active" value="1" @checked(old('is_active', $exercise->is_active ?? true))>
                <label class="form-check-label" for="exercise-is-active">Показувати вправу учням</label>
            </div>
        </div>

        <div class="admin-form-actions mt-4">
            <a href="{{ route('admin.course.lesson.exercises.index', $lesson) }}" class="admin-btn-soft">
                <i class="bi bi-x-lg"></i> Скасувати
            </a>
            <button type="submit" class="admin-btn-primary">
                <i class="bi bi-check2"></i> {{ isset($exercise) ? 'Оновити вправу' : 'Додати вправу' }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const list = document.querySelector('#exercise-pairs');
            const addButton = document.querySelector('#add-exercise-pair');
            const template = document.querySelector('#exercise-pair-template');

            if (!list || !addButton || !template) {
                return;
            }

            function renumberPairs() {
                const rows = [...list.querySelectorAll('.lesson-exercise-pair')];
                const minimumPairs = Number(list.dataset.minPairs || 2);

                rows.forEach((row, index) => {
                    row.querySelector('.lesson-exercise-pair-number').textContent = index + 1;
                    row.querySelectorAll('[data-field]').forEach(field => {
                        field.name = `pairs[${index}][${field.dataset.field}]`;
                    });
                });

                list.querySelectorAll('.lesson-exercise-remove-pair').forEach(button => {
                    button.disabled = rows.length <= minimumPairs;
                });

                addButton.disabled = rows.length >= 30;
            }

            addButton.addEventListener('click', function () {
                if (list.children.length >= 30) {
                    return;
                }

                list.appendChild(template.content.cloneNode(true));
                renumberPairs();
                list.lastElementChild.querySelector('input, select, textarea')?.focus();
            });

            list.addEventListener('click', function (event) {
                const button = event.target.closest('.lesson-exercise-remove-pair');
                const minimumPairs = Number(list.dataset.minPairs || 2);
                if (!button || list.children.length <= minimumPairs) {
                    return;
                }

                button.closest('.lesson-exercise-pair').remove();
                renumberPairs();
            });

            renumberPairs();
        });
    </script>
@endpush
