<section class="admin-panel">
    <div class="admin-panel-header">
        <h2 class="admin-panel-title">Список тестів</h2>
        <span class="admin-badge admin-badge-muted">Усього: {{ $tests->count() }}</span>
    </div>

    <div class="admin-panel-body">
        @if($tests->isEmpty())
            <div class="admin-empty-state">
                <div class="admin-empty-icon">
                    <i class="bi bi-ui-checks"></i>
                </div>
                <h3>Тестів ще немає</h3>
                <p>Додайте перше питання для цього уроку.</p>
            </div>
        @else
            <ul id="sortable-tests" class="list-group list-group-flush sortable-tests-list">
                @foreach($tests->sortBy('position') as $test)
                    <li class="list-group-item d-flex justify-content-between align-items-center"
                        data-id="{{ $test->id }}">
                        <div>
                            <strong>#{{ $test->position }}</strong> {{ $test->question }}
                        </div>

                        <div class="admin-actions">
                            <a href="{{ route('admin.course.lesson.test.edit', ['lesson' => $lesson->id, 'test' => $test->id]) }}"
                               class="admin-btn-warning">
                                <i class="bi bi-pencil"></i>
                                Редагувати
                            </a>

                            <form action="{{ route('admin.course.lesson.test.destroy', ['lesson' => $lesson->id, 'test' => $test->id]) }}"
                                  method="POST"
                                  onsubmit="return confirm('Видалити тест?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn-danger">
                                    <i class="bi bi-trash"></i>
                                    Видалити
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</section>
