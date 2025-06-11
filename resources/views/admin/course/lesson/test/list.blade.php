<div class="card">
    <div class="card-header">
        <h4 class="mb-0">Список тестів</h4>
    </div>
    <div class="card-body">
        @if($tests->isEmpty())
            <div class="alert alert-info">Тестів ще немає.</div>
        @else
            <ul id="sortable-tests" class="list-group">
                @foreach($tests->sortBy('position') as $test)
                    <li class="list-group-item d-flex justify-content-between align-items-center" data-id="{{ $test->id }}">
                        <div>
                            <strong>#{{ $test->position }}</strong> {{ $test->question }}
                        </div>
                        <div class="btn-group">
                            <a href="{{ route('admin.course.lesson.test.edit', ['lesson' => $lesson->id, 'test' => $test->id]) }}" class="btn-custom btn-sm">
                                <i class="fa-solid fa-pencil"></i> Редагувати
                            </a>
                            <form action="{{ route('admin.course.lesson.test.destroy', ['lesson' => $lesson->id, 'test' => $test->id]) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-custom btn-sm" onclick="return confirm('Ви впевнені?')">
                                    <i class="fa-solid fa-trash"></i> Видалити
                                </button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
</div>
