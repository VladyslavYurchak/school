@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-plus-lg"></i>
                            Новий пост
                        </span>
                        <h1 class="admin-title">Створити пост</h1>
                        <p class="admin-subtitle">
                            Додайте коротку публікацію для сайту. Опубліковані пости можуть з'являтися на головній.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.post.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('admin.post.store') }}" method="POST" enctype="multipart/form-data" class="admin-panel admin-form admin-form-card">
                @csrf

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані поста</h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="title" class="admin-form-label">Заголовок</label>
                        <input type="text"
                               name="title"
                               id="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title') }}"
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
                                  required>{{ old('content') }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    @include('admin.components.square-image-editor', [
                        'editorId' => 'post-image',
                    ])

                    <div class="admin-form-section">
                        <label for="is_published" class="admin-form-label">Статус</label>
                        <select id="is_published" name="is_published" class="form-select @error('is_published') is-invalid @enderror">
                            <option value="1" @selected(old('is_published', 1) == 1)>Опублікувати</option>
                            <option value="0" @selected(old('is_published', 1) == 0)>Зберегти як чернетку</option>
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
                            Створити пост
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
