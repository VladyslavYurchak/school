<form class="admin-panel admin-form admin-form-card"
      action="{{ isset($event) ? route('admin.event.update', $event) : route('admin.event.store') }}"
      method="POST"
      enctype="multipart/form-data">
    @csrf
    @if(isset($event))
        @method('PATCH')
    @endif

    <div class="admin-panel-header">
        <h2 class="admin-panel-title">Дані події</h2>
        @if(isset($event))
            <span class="admin-badge {{ $event->is_published ? 'admin-badge-free' : 'admin-badge-muted' }}">
                {{ $event->is_published ? 'Опубліковано' : 'Чернетка' }}
            </span>
        @endif
    </div>

    <div class="admin-panel-body">
        <div class="admin-form-section">
            <label for="title" class="admin-form-label">Назва події</label>
            <input type="text"
                   name="title"
                   id="title"
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $event->title ?? '') }}"
                   maxlength="255"
                   required>
            @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="admin-form-section">
            <label for="start_date" class="admin-form-label">Дата події</label>
            <input type="date"
                   name="start_date"
                   id="start_date"
                   class="form-control @error('start_date') is-invalid @enderror"
                   value="{{ old('start_date', isset($event) ? $event->start_date->format('Y-m-d') : '') }}"
                   required>
            @error('start_date')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @include('admin.components.square-image-editor', [
            'editorId' => 'event-image',
            'currentImageUrl' => isset($event) ? $event->image_url : '',
            'currentImageValue' => $event->image ?? '',
        ])

        <div class="admin-form-section">
            <label for="is_published" class="admin-form-label">Статус</label>
            <select id="is_published" name="is_published" class="form-select @error('is_published') is-invalid @enderror">
                <option value="1" @selected(old('is_published', $event->is_published ?? 1) == 1)>Опублікувати</option>
                <option value="0" @selected(old('is_published', $event->is_published ?? 1) == 0)>Зберегти як чернетку</option>
            </select>
            @error('is_published')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="admin-form-actions">
            <a href="{{ route('admin.event.index') }}" class="admin-btn-soft">
                <i class="bi bi-x-lg"></i>
                Скасувати
            </a>
            <button type="submit" class="admin-btn-primary">
                <i class="bi bi-check2"></i>
                {{ isset($event) ? 'Оновити подію' : 'Створити подію' }}
            </button>
        </div>
    </div>
</form>
