<form action="{{ isset($schoolRule) ? route('admin.school-rules.update', $schoolRule->id) : route('admin.school-rules.store') }}"
      method="POST">
    @csrf
    @if(isset($schoolRule))
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-12">
            <label class="form-label">Назва правила</label>
            <input type="text"
                   name="title"
                   class="form-control"
                   required
                   value="{{ old('title', $schoolRule->title ?? '') }}">
        </div>

        <div class="col-md-12">
            <label class="form-label">Текст правила</label>
            <textarea name="content"
                      class="form-control"
                      rows="8">{{ old('content', $schoolRule->content ?? '') }}</textarea>
        </div>

        <div class="col-md-4">
            <label class="form-label">Порядок</label>
            <input type="number"
                   name="sort_order"
                   class="form-control"
                   min="0"
                   value="{{ old('sort_order', $schoolRule->sort_order ?? 0) }}">
        </div>

        <div class="col-md-4">
            <label class="form-label">Активне</label>
            <select name="is_active" class="form-select">
                <option value="1" @selected(old('is_active', $schoolRule->is_active ?? 1) == 1)>Так</option>
                <option value="0" @selected(old('is_active', $schoolRule->is_active ?? 1) == 0)>Ні</option>
            </select>
        </div>

        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary">
                {{ isset($schoolRule) ? 'Оновити' : 'Зберегти' }}
            </button>
        </div>
    </div>
</form>
