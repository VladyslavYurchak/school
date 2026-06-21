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
                        <h1 class="admin-title">Редагувати правило</h1>
                        <p class="admin-subtitle">
                            Оновіть текст, порядок або видимість правила на публічній сторінці.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('rules.index') }}" class="admin-btn-soft" target="_blank" rel="noopener">
                            <i class="bi bi-eye"></i>
                            На сайті
                        </a>
                        <a href="{{ route('admin.school-rules.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            @include('admin.school_rules.form', ['schoolRule' => $schoolRule])
        </div>
    </div>
@endsection
