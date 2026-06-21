@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-plus-lg"></i>
                            Нове правило
                        </span>
                        <h1 class="admin-title">Додати правило школи</h1>
                        <p class="admin-subtitle">
                            Створіть короткий зрозумілий пункт правил. Після збереження активне правило буде видно на сайті.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.school-rules.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            @include('admin.school_rules.form')
        </div>
    </div>
@endsection
