<section class="admin-panel">
    <div class="admin-panel-header">
        <h2 class="admin-panel-title">Попередній перегляд тестового блоку</h2>
    </div>

    <div class="admin-panel-body">
        @if($tests->isEmpty())
            <div class="admin-empty-state">
                <div class="admin-empty-icon">
                    <i class="bi bi-eye"></i>
                </div>
                <h3>Немає що показати</h3>
                <p>Попередній перегляд зʼявиться після додавання тестових питань.</p>
            </div>
        @else
            @foreach($tests->sortBy('position') as $test)
                <div class="admin-content-box mb-3">
                    <h3 class="admin-panel-title mb-3">Питання #{{ $test->position }}</h3>
                    <p class="test-preview-question fw-bold mb-3">{{ $test->question }}</p>

                    @php
                        $letters = ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Є', 'Ж', 'З', 'И', 'І', 'Ї', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ь', 'Ю', 'Я'];
                    @endphp

                    @if($test->options && $test->options->count())
                        <div class="d-grid gap-2">
                            @foreach($test->options as $index => $option)
                                <label class="test-preview-option {{ $option->is_correct ? 'is-correct' : '' }}">
                                    <input class="form-check-input me-2" type="checkbox" disabled
                                           id="option-{{ $option->id }}"
                                           name="test-{{ $test->id }}[]"
                                           value="{{ $option->id }}"
                                        {{ $option->is_correct ? 'checked' : '' }}>
                                    <strong>{{ $letters[$index] ?? $index + 1 }}.</strong>
                                    <span class="{{ $option->is_correct ? 'text-success fw-bold' : '' }}">{{ $option->option_text }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">Варіанти відповідей відсутні.</p>
                    @endif
                </div>
            @endforeach
        @endif
    </div>
</section>
