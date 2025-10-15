<div class="container my-4">
    <h3 class="mb-4">Попередній перегляд тестового блоку</h3>

    @if($tests->isEmpty())
        <div class="alert alert-info">Тестів ще немає.</div>
    @else
        @foreach($tests->sortBy('position') as $test)
            <div class="card mb-4 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title fw-bold mb-3 text-start">Питання #{{ $test->position }}</h5>
                    <p class="card-text mb-4 fw-bold"
                       style="white-space: pre-wrap; font-size: 1.1rem; line-height: 1.5; text-align: left !important; margin-top: 1rem;">
                        {{ $test->question }}
                    </p>

                    <p class="text-muted fst-italic mb-3">Позначте правильну відповідь (або декілька):</p>

                    @php
                        $letters = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Є', 'Ж', 'З', 'И', 'І', 'Ї', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь', 'Ю', 'Я'];
                    @endphp

                    @if($test->options && $test->options->count())
                        <form>
                            @foreach($test->options as $index => $option)
                                <div class="form-check mb-2 d-flex align-items-start">
                                    <input class="form-check-input mt-1" type="checkbox" disabled
                                           id="option-{{ $option->id }}"
                                           name="test-{{ $test->id }}[]"
                                           value="{{ $option->id }}"
                                        {{ $option->is_correct ? 'checked' : '' }}>
                                    <label class="form-check-label
                                        {{ $option->is_correct ? 'text-success fw-bold' : '' }} ms-2"
                                           for="option-{{ $option->id }}">
                                        <strong>{{ $letters[$index] ?? $index + 1 }}.</strong> {{ $option->option_text }}
                                    </label>
                                </div>
                            @endforeach
                        </form>
                    @else
                        <p class="text-muted fst-italic">Варіанти відповідей відсутні.</p>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
