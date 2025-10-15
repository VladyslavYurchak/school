<form action="{{ isset($teacher) ? route('admin.teachers.update', $teacher->id) : route('admin.teachers.store') }}" method="POST">
    @csrf
    @if(isset($teacher))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Ім’я</label>
            <input type="text" name="first_name" class="form-control" required value="{{ old('first_name', $teacher->first_name ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Прізвище</label>
            <input type="text" name="last_name" class="form-control" required value="{{ old('last_name', $teacher->last_name ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Телефон</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $teacher->phone ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->user->email ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна заняття</label>
            <input type="number" name="lesson_price" class="form-control" step="0.01" value="{{ old('lesson_price', $teacher->lesson_price ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна групового заняття</label>
            <input type="number" name="group_lesson_price" class="form-control" step="0.01" value="{{ old('group_lesson_price', $teacher->group_lesson_price ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна парного заняття</label>
            <input type="number" name="pair_lesson_price" class="form-control" step="0.01" value="{{ old('pair_lesson_price', $teacher->pair_lesson_price ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Ціна пробного заняття</label>
            <input type="number" name="trial_lesson_price" class="form-control" step="0.01" value="{{ old('trial_lesson_price', $teacher->trial_lesson_price ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Активний</label>
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
