@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-eye"></i>
                            Перегляд
                        </span>
                        <h1 class="admin-title">{{ $post->title }}</h1>
                        <p class="admin-subtitle">
                            Створено {{ $post->created_at->format('d.m.Y H:i') }}
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.post.edit', $post) }}" class="admin-btn-primary">
                            <i class="bi bi-pencil"></i>
                            Редагувати
                        </a>
                        <a href="{{ route('admin.post.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Вміст поста</h2>
                    <span class="admin-badge {{ $post->is_published ? 'admin-badge-free' : 'admin-badge-muted' }}">
                        {{ $post->is_published ? 'Опубліковано' : 'Чернетка' }}
                    </span>
                </div>

                <div class="admin-panel-body">
                    @if($post->image)
                        <div class="admin-post-preview-image mb-3">
                            <img src="{{ $post->image_url }}" alt="{{ $post->title }}">
                        </div>
                    @endif

                    <div class="admin-content-box">
                        {!! nl2br(e($post->content)) !!}
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
