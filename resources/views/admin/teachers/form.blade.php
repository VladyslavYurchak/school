<form action="{{ isset($teacher) ? route('admin.teachers.update', $teacher->id) : route('admin.teachers.store') }}" method="POST">
    @csrf
    @if(isset($teacher))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Користувач</label>
            <select name="user_id" class="form-select" required @if(isset($teacher)) disabled @endif>
                <option value="">Оберіть користувача</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}"
                        @selected(old('user_id', $teacher->user_id ?? '') == $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
            </select>

            @if(isset($teacher))
                <input type="hidden" name="user_id" value="{{ $teacher->user_id }}">
            @endif
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна за індивідуальне заняття (грн)</label>
            <input type="number" name="lesson_price" class="form-control" step="0.01" min="0"
                   value="{{ old('lesson_price', $teacher->lesson_price ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна за групове заняття (грн)</label>
            <input type="number" step="0.01" min="0" name="group_lesson_price" class="form-control"
                   value="{{ old('group_lesson_price', $teacher->group_lesson_price ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна за парне заняття (грн)</label>
            <input type="number" step="0.01" min="0" name="pair_lesson_price" class="form-control"
                   value="{{ old('pair_lesson_price', $teacher->pair_lesson_price ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна за пробне заняття (грн)</label>
            <input type="number" step="0.01" min="0" name="trial_lesson_price" class="form-control"
                   value="{{ old('trial_lesson_price', $teacher->trial_lesson_price ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Активний?</label>
            <select name="is_active" class="form-select">
                <option value="1" @selected(old('is_active', $teacher->is_active ?? 1) == 1)>Так</option>
                <option value="0" @selected(old('is_active', $teacher->is_active ?? 1) == 0)>Ні</option>
            </select>
        </div>

        <div class="col-md-12">
            <label class="form-label">Нотатки</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $teacher->note ?? '') }}</textarea>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">
                {{ isset($teacher) ? 'Оновити' : 'Зберегти' }}
            </button>
        </div>
    </div>
</form>
