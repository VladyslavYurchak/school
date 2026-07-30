@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-pencil"></i>
                            Редагування
                        </span>
                        <h1 class="admin-title">Редагувати пост</h1>
                        <p class="admin-subtitle">
                            Оновіть заголовок, текст або фото публікації.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.post.show', $post) }}" class="admin-btn-soft">
                            <i class="bi bi-eye"></i>
                            Перегляд
                        </a>
                        <a href="{{ route('admin.post.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            <form action="{{ route('admin.post.update', $post) }}" method="POST" enctype="multipart/form-data" class="admin-panel admin-form admin-form-card">
                @csrf
                @method('PATCH')

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані поста</h2>
                    <span class="admin-badge {{ $post->is_published ? 'admin-badge-free' : 'admin-badge-muted' }}">
                        {{ $post->is_published ? 'Опубліковано' : 'Чернетка' }}
                    </span>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="title" class="admin-form-label">Заголовок</label>
                        <input type="text"
                               name="title"
                               id="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $post->title) }}"
                               maxlength="255"
                               required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="admin-form-section">
                        <label for="content" class="admin-form-label">Зміст</label>
                        <textarea name="content"
                                  id="content"
                                  class="form-control @error('content') is-invalid @enderror"
                                  rows="8"
                                  required>{{ old('content', $post->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @include('admin.components.square-image-editor', [
                        'editorId' => 'post-image',
                        'currentImageUrl' => $post->image_url,
                        'currentImageValue' => $post->image,
                    ])

                    <div class="admin-form-section">
                        <label for="is_published" class="admin-form-label">Статус</label>
                        <select id="is_published" name="is_published" class="form-select @error('is_published') is-invalid @enderror">
                            <option value="1" @selected(old('is_published', $post->is_published) == 1)>Опубліковано</option>
                            <option value="0" @selected(old('is_published', $post->is_published) == 0)>Чернетка</option>
                        </select>
                        @error('is_published')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="admin-form-actions">
                        <a href="{{ route('admin.post.index') }}" class="admin-btn-soft">
                            <i class="bi bi-x-lg"></i>
                            Скасувати
                        </a>
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Оновити пост
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
