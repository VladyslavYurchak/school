@php
    $isDictation = $exercise->type === \App\Models\LessonExercise::TYPE_DICTATION;
    $blockModifier = $isDictation ? 'dictation' : 'transformation';
    $icon = $isDictation ? 'bi-volume-up' : 'bi-arrow-repeat';
@endphp

<section class="lesson-system-block lesson-system-block--{{ $blockModifier }}"
         data-text-answer-exercise
         data-exercise-id="{{ $exercise->id }}">
    <div class="lesson-system-heading">
        <span class="lesson-system-icon"><i class="bi {{ $icon }}"></i></span>
        <div>
            <div class="lesson-system-kicker">Інтерактивна вправа</div>
            <h2>{{ $exercise->title }}</h2>
        </div>
    </div>

    @if($exercise->description)
        <p class="lesson-exercise-description">{{ $exercise->description }}</p>
    @endif

    <div class="lesson-text-answer-list">
        @foreach($exercise->items as $item)
            @php
                $acceptedAnswers = data_get($item->settings, 'accepted_answers', []);
                if (empty($acceptedAnswers)) {
                    $acceptedAnswers = [$item->answer];
                }
            @endphp
            <article class="lesson-text-answer-item"
                     data-text-answer-item
                     data-accepted-answers="{{ json_encode($acceptedAnswers, JSON_UNESCAPED_UNICODE) }}"
                     data-primary-answer="{{ $item->answer }}">
                <div class="lesson-text-answer-header">
                    <span class="lesson-fill-blank-number">{{ $loop->iteration }}</span>
                    @if($item->prompt)
                        <p>{{ $item->prompt }}</p>
                    @else
                        <p>Прослухайте аудіо та запишіть почуте</p>
                    @endif
                </div>

                @if($isDictation && $item->audio_path)
                    <audio controls preload="metadata" class="lesson-dictation-audio">
                        <source src="{{ \Illuminate\Support\Facades\Storage::url($item->audio_path) }}">
                        Ваш браузер не підтримує відтворення аудіо.
                    </audio>
                @endif

                <label class="lesson-text-answer-label" for="lesson-answer-{{ $exercise->id }}-{{ $item->id }}">
                    {{ $isDictation ? 'Ваша відповідь' : 'Перетворене речення' }}
                </label>
                <input type="text"
                       id="lesson-answer-{{ $exercise->id }}-{{ $item->id }}"
                       class="lesson-text-answer-control"
                       data-text-answer-control
                       autocomplete="off">
                <span class="lesson-text-answer-feedback" data-text-answer-feedback aria-live="polite"></span>
            </article>
        @endforeach
    </div>

    <div class="lesson-fill-blank-actions">
        <button type="button" class="lesson-submit-button" data-text-answer-check>
            <i class="bi bi-check2-circle"></i> Перевірити
        </button>
        <button type="button" class="lesson-matching-reset" data-text-answer-reset>
            <i class="bi bi-arrow-counterclockwise"></i> Спробувати ще раз
        </button>
        <strong class="lesson-fill-blank-result" data-text-answer-result aria-live="polite"></strong>
    </div>
</section>
