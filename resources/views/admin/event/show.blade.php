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
                        <h1 class="admin-title">{{ $event->title }}</h1>
                        <p class="admin-subtitle">
                            Дата події: {{ $event->start_date->format('d.m.Y') }}
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.event.edit', $event) }}" class="admin-btn-primary">
                            <i class="bi bi-pencil"></i>
                            Редагувати
                        </a>
                        <a href="{{ route('admin.event.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Дані події</h2>
                    <span class="admin-badge {{ $event->is_published ? 'admin-badge-free' : 'admin-badge-muted' }}">
                        {{ $event->is_published ? 'Опубліковано' : 'Чернетка' }}
                    </span>
                </div>

                <div class="admin-panel-body">
                    @if($event->image)
                        <div class="admin-post-preview-image mb-3">
                            <img src="{{ $event->image_url }}" alt="{{ $event->title }}">
                        </div>
                    @endif

                    <div class="admin-content-box">
                        <strong>Дата:</strong> {{ $event->start_date->format('d.m.Y') }}<br>
                        <strong>Створено:</strong> {{ $event->created_at->format('d.m.Y H:i') }}
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
