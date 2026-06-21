@php
    $currentType = old('type', $block->type ?? $type);
    $typeLabels = [
        'text' => 'Текст',
        'video' => 'YouTube-відео',
        'audio' => 'Аудіо',
        'image' => 'Зображення',
        'pdf' => 'PDF',
    ];
@endphp

<form action="{{ isset($block)
        ? route('admin.course.lesson.blocks.update', [$lesson, $block])
        : route('admin.course.lesson.blocks.store', $lesson) }}"
      method="POST" enctype="multipart/form-data" class="admin-panel admin-form">
    @csrf
    @isset($block) @method('PUT') @endisset

    <input type="hidden" name="type" value="{{ $currentType }}">

    <div class="admin-panel-header">
        <h2 class="admin-panel-title">{{ $typeLabels[$currentType] }}</h2>
        <span class="admin-badge admin-badge-muted">{{ isset($block) ? 'Редагування' : 'Новий блок' }}</span>
    </div>

    <div class="admin-panel-body">
        <div class="admin-form-section">
            <label for="block-title" class="admin-form-label">Заголовок <span class="text-muted">(необов’язково)</span></label>
            <input type="text" id="block-title" name="title"
                   class="form-control @error('title') is-invalid @enderror"
                   value="{{ old('title', $block->title ?? '') }}" maxlength="255">
            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        @if($currentType === 'video')
            <div class="admin-form-section">
                <label for="block-video-url" class="admin-form-label">Посилання на YouTube</label>
                <input type="url" id="block-video-url" name="video_url"
                       class="form-control @error('video_url') is-invalid @enderror"
                       placeholder="https://www.youtube.com/watch?v=..."
                       value="{{ old('video_url', $block->video_url ?? '') }}" required>
                @error('video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        @endif

        @if(in_array($currentType, ['audio', 'image', 'pdf'], true))
            @php
                $maxFileMb = ['audio' => 30, 'image' => 8, 'pdf' => 20][$currentType];
            @endphp
            <div class="admin-form-section">
                <label for="block-media-file" class="admin-form-label">
                    {{ isset($block) && $block->media_path ? 'Замінити файл' : 'Файл' }}
                </label>
                <input type="file" id="block-media-file" name="media_file"
                       class="form-control @error('media_file') is-invalid @enderror"
                       data-max-bytes="{{ $maxFileMb * 1024 * 1024 }}"
                       data-max-label="{{ $maxFileMb }} MB"
                       accept="{{ $currentType === 'audio' ? '.mp3,.wav,.ogg,.m4a' : ($currentType === 'image' ? 'image/jpeg,image/png,image/webp' : 'application/pdf') }}"
                       {{ isset($block) && $block->media_path ? '' : 'required' }}>
                @error('media_file') <div class="invalid-feedback">{{ $message }}</div> @enderror

                <div class="form-text">
                    Максимальний розмір: {{ $maxFileMb }} MB.
                    @if($currentType === 'audio') Підтримуються MP3, WAV, OGG та M4A. @endif
                </div>

                @if(isset($block) && $block->media_path)
                    <div class="form-text">Поточний файл: {{ $block->media_name }}</div>
                @endif
            </div>
        @endif

        <div class="admin-form-section">
            <label for="block-content-editor" class="admin-form-label">
                {{ $currentType === 'text' ? 'Текст блока' : 'Опис' }}
                @if($currentType !== 'text') <span class="text-muted">(необов’язково)</span> @endif
            </label>
            <textarea id="block-content-editor" name="content" rows="9"
                      class="form-control @error('content') is-invalid @enderror">{{ old('content', $block->content ?? '') }}</textarea>
            @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="admin-form-section">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" id="block-is-active" name="is_active" value="1"
                       @checked(old('is_active', $block->is_active ?? true))>
                <label class="form-check-label" for="block-is-active">Показувати блок учням</label>
            </div>
        </div>

        <div class="admin-form-actions">
            <a href="{{ route('admin.course.lesson.blocks.index', $lesson) }}" class="admin-btn-soft">
                <i class="bi bi-x-lg"></i> Скасувати
            </a>
            <button type="submit" class="admin-btn-primary">
                <i class="bi bi-check2"></i> {{ isset($block) ? 'Оновити' : 'Додати блок' }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editor = document.querySelector('#block-content-editor');
            const mediaInput = document.querySelector('#block-media-file');

            if (mediaInput) {
                mediaInput.addEventListener('change', function () {
                    const file = this.files[0];
                    const maxBytes = Number(this.dataset.maxBytes);
                    const message = file && file.size > maxBytes
                        ? `Файл завеликий. Максимальний розмір: ${this.dataset.maxLabel}.`
                        : '';

                    this.setCustomValidity(message);
                    this.reportValidity();

                    if (message) {
                        this.value = '';
                    }
                });
            }

            if (editor) {
                ClassicEditor.create(editor, {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo']
                }).catch(error => console.error(error));
            }
        });
    </script>
@endpush
