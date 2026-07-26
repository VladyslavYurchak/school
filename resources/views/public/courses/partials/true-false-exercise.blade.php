<section class="lesson-system-block lesson-system-block--true-false"
         data-true-false-exercise
         data-exercise-id="{{ $exercise->id }}">
    <div class="lesson-system-heading">
        <span class="lesson-system-icon"><i class="bi bi-check2-square"></i></span>
        <div>
            <div class="lesson-system-kicker">Інтерактивна вправа</div>
            <h2>{{ $exercise->title }}</h2>
        </div>
    </div>

    @if($exercise->description)
        <p class="lesson-exercise-description">{{ $exercise->description }}</p>
    @endif

    <div class="lesson-true-false-list">
        @foreach($exercise->items as $item)
            <article class="lesson-true-false-item"
                     data-true-false-item
                     data-answer="{{ $item->answer }}"
                     data-explanation="{{ data_get($item->settings, 'explanation') }}">
                <div class="lesson-true-false-question">
                    <span class="lesson-fill-blank-number">{{ $loop->iteration }}</span>
                    <p>{{ $item->prompt }}</p>
                </div>
                <div class="lesson-true-false-options"
                     role="group"
                     aria-label="{{ $exercise->title }}: твердження {{ $loop->iteration }}">
                    <button type="button" data-true-false-option data-value="true">
                        <i class="bi bi-check-lg"></i> Правда
                    </button>
                    <button type="button" data-true-false-option data-value="false">
                        <i class="bi bi-x-lg"></i> Неправда
                    </button>
                </div>
                <span class="lesson-true-false-feedback" data-true-false-feedback aria-live="polite"></span>
            </article>
        @endforeach
    </div>

    <div class="lesson-fill-blank-actions">
        <button type="button" class="lesson-submit-button" data-true-false-check>
            <i class="bi bi-check2-circle"></i> Перевірити
        </button>
        <button type="button" class="lesson-matching-reset" data-true-false-reset>
            <i class="bi bi-arrow-counterclockwise"></i> Спробувати ще раз
        </button>
        <strong class="lesson-fill-blank-result" data-true-false-result aria-live="polite"></strong>
    </div>
</section>
