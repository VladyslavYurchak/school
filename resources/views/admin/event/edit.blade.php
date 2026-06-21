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
                        <h1 class="admin-title">Редагувати подію</h1>
                        <p class="admin-subtitle">
                            Оновіть назву, дату, фото або видимість події на сайті.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.event.show', $event) }}" class="admin-btn-soft">
                            <i class="bi bi-eye"></i>
                            Перегляд
                        </a>
                        <a href="{{ route('admin.event.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            @include('admin.event.form', ['event' => $event])
        </div>
    </div>
@endsection
