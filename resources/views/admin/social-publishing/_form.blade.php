@php
    $selectedPlatforms = old(
        'platforms',
        isset($publication) ? $publication->targets->pluck('platform')->all() : []
    );
    $platforms = [
        'facebook' => ['label' => 'Facebook', 'icon' => 'bi-facebook'],
        'instagram' => ['label' => 'Instagram', 'icon' => 'bi-instagram'],
        'tiktok' => ['label' => 'TikTok', 'icon' => 'bi-tiktok'],
    ];
@endphp

<div class="admin-panel-header">
    <h2 class="admin-panel-title">Публікація</h2>
    <span class="admin-badge admin-badge-muted">Безпечний режим</span>
</div>

<div class="admin-panel-body">
    <div class="social-safety-banner mb-3" role="status">
        <i class="bi bi-shield-check"></i>
        <div>
            <strong>Реальна відправка вимкнена.</strong>
            <div>Чернетки та тестові запуски зберігаються лише в цьому модулі.</div>
        </div>
    </div>

    <div class="admin-form-section">
        <label for="title" class="admin-form-label">Внутрішня назва</label>
        <input type="text"
               name="title"
               id="title"
               class="form-control @error('title') is-invalid @enderror"
               value="{{ old('title', $publication->title ?? '') }}"
               maxlength="255"
               required>
        <div class="form-text">Її бачать лише адміністратори.</div>
        @error('title')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="admin-form-section">
        <label for="caption" class="admin-form-label">Текст публікації</label>
        <textarea name="caption"
                  id="caption"
                  class="form-control @error('caption') is-invalid @enderror"
                  rows="8"
                  maxlength="2200">{{ old('caption', $publication->caption ?? '') }}</textarea>
        <div class="form-text">До 2200 символів. Хештеги можна додати в кінці тексту.</div>
        @error('caption')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <fieldset class="admin-form-section">
        <legend class="admin-form-label">Куди публікувати</legend>
        <div class="social-platform-grid">
            @foreach($platforms as $value => $platform)
                <div class="social-platform-option">
                    <input type="checkbox"
                           name="platforms[]"
                           id="platform-{{ $value }}"
                           value="{{ $value }}"
                           @checked(in_array($value, $selectedPlatforms, true))>
                    <label for="platform-{{ $value }}">
                        <span class="social-platform-icon"><i class="bi {{ $platform['icon'] }}"></i></span>
                        <span class="social-platform-copy">
                            <strong>{{ $platform['label'] }}</strong>
                            <small>Окремий результат у журналі</small>
                        </span>
                    </label>
                </div>
            @endforeach
        </div>
        @error('platforms')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror
        @error('platforms.*')
            <div class="text-danger small mt-2">{{ $message }}</div>
        @enderror
    </fieldset>

    <div class="admin-form-section">
        <label for="media_file" class="admin-form-label">Фото або відео</label>
        <input type="file"
               name="media_file"
               id="media_file"
               class="form-control @error('media_file') is-invalid @enderror"
               accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm">
        <div class="form-text">JPG, PNG, WEBP, MP4, MOV або WEBM; до 100 МБ.</div>
        @error('media_file')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror

        @if(isset($publication) && $publication->media_path)
            <div class="social-media-preview mt-3">
                @if($publication->media_type === 'video')
                    <video controls preload="metadata">
                        <source src="{{ asset('storage/'.$publication->media_path) }}">
                    </video>
                @else
                    <img src="{{ asset('storage/'.$publication->media_path) }}" alt="Медіа чернетки">
                @endif
            </div>
            <div class="form-check mt-2">
                <input type="checkbox" name="remove_media" id="remove_media" value="1" class="form-check-input">
                <label for="remove_media" class="form-check-label">Видалити поточний файл</label>
            </div>
        @endif
    </div>

    <div class="admin-form-actions">
        <a href="{{ route('admin.social-publishing.index') }}" class="admin-btn-soft">
            <i class="bi bi-x-lg"></i>
            Скасувати
        </a>
        <button type="submit" class="admin-btn-primary">
            <i class="bi bi-floppy"></i>
            Зберегти чернетку
        </button>
    </div>
</div>
