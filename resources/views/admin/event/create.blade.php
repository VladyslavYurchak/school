@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-plus-lg"></i>
                            Нова подія
                        </span>
                        <h1 class="admin-title">Створити подію</h1>
                        <p class="admin-subtitle">
                            Додайте майбутню подію школи для головної сторінки.
                        </p>
                    </div>

                    <div class="admin-actions">
                        <a href="{{ route('admin.event.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i>
                            До списку
                        </a>
                    </div>
                </div>
            </section>

            @include('admin.event.form')
        </div>
    </div>
@endsection
