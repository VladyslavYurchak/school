<div class="card shadow-lg border-0">
    <div class="card-header bg-white">
        <h4 class="fw-bold text-dark mb-0">Список тестів</h4>
    </div>
    <div class="card-body">
        @if($tests->isEmpty())
            <div class="alert alert-info shadow-sm">Тестів ще немає.</div>
        @else
            <ul id="sortable-tests" class="list-group list-group-flush">
                @foreach($tests->sortBy('position') as $test)
                    <li class="list-group-item d-flex justify-content-between align-items-center shadow-sm mb-2 rounded"
                        data-id="{{ $test->id }}">
                        <div>
                            <strong>#{{ $test->position }}</strong> {{ $test->question }}
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('admin.course.lesson.test.edit', ['lesson' => $lesson->id, 'test' => $test->id]) }}"
                               class="btn btn-warning btn-sm shadow-sm">
                                ✏️ Редагувати
                            </a>
                            <form action="{{ route('admin.course.lesson.test.destroy', ['lesson' => $lesson->id, 'test' => $test->id]) }}"
                                  method="POST"
                                  onsubmit="return confirm('Ви впевнені?')"
                                  class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm shadow-sm">
                                    🗑️ Видалити
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
