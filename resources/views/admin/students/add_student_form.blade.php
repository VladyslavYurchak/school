<form action="{{ isset($student) ? route('admin.students.update', $student->id) : route('admin.students.store') }}" method="POST">
    @csrf
    @if(isset($student))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Ім’я</label>
            <input type="text" name="first_name" class="form-control" required value="{{ old('first_name', $student->first_name ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Прізвище</label>
            <input type="text" name="last_name" class="form-control" required value="{{ old('last_name', $student->last_name ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Номер телефону</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $student->phone ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $student->email ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Закріплений викладач</label>
            <select name="teacher_id" class="form-select">
                <option value="">— Оберіть викладача —</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}"
                        @selected(old('teacher_id', $student->teacher_id ?? '') == $teacher->id)>
                        {{ $teacher->full_name ?? $teacher->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- 🔽 Нове поле — Абонемент --}}
        <div class="col-md-6">
            <label class="form-label">Абонемент</label>
            <select name="subscription_id" class="form-select">
                <option value="">— Без абонементу —</option>
                @foreach ($subscriptionTemplates as $template)
                    <option value="{{ $template->id }}"
                        @selected(old('subscription_id', $student->subscription_id ?? '') == $template->id)>
                        {{ $template->title }} — {{ $template->lessons_per_week }} раз/тижд — {{ number_format($template->price, 2, ',', ' ') }} грн
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label">Дата народження</label>
            <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', $student->birth_date ?? '') }}">
        </div>

        <div class="col-md-6">
            <label class="form-label">Контакт батьків</label>
            <input type="text" name="parent_contact" class="form-control" value="{{ old('parent_contact', $student->parent_contact ?? '') }}">
        </div>

        <div class="col-md-3">
            <label class="form-label">Акт/Неакт</label>
            <select name="is_active" class="form-select">
                <option value="1" @selected(old('is_active', $student->is_active ?? 1) == 1)>Так</option>
                <option value="0" @selected(old('is_active', $student->is_active ?? 1) == 0)>Ні</option>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label">Примітка</label>
            <textarea name="note" class="form-control" rows="3">{{ old('note', $student->note ?? '') }}</textarea>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">
                {{ isset($student) ? 'Оновити' : 'Зберегти' }}
            </button>
        </div>
    </div>
</form>
