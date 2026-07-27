@extends('public.layouts.main')

@section('content')
    <div class="account-page">
        <div class="container">
            <section class="account-pending">
                <span class="account-pending-icon" aria-hidden="true">
                    <i class="bi bi-person-check"></i>
                </span>

                <p class="account-pending-eyebrow">Кабінет учня</p>
                <h1>Акаунт успішно створено</h1>
                <p>
                    Адміністратор школи має підключити вашу картку учня до
                    <strong>{{ $user->email }}</strong>. Після цього тут з’являться
                    розклад, абонемент, оплати та історія занять.
                </p>

                <div class="account-pending-actions">
                    <a href="{{ route('courses.index') }}" class="btn-brand">
                        <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                        Переглянути курси
                    </a>
                    <button
                        type="button"
                        class="btn-brand-outline"
                        data-bs-toggle="modal"
                        data-bs-target="#trialLessonRequestModal"
                    >
                        <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                        Записатися на заняття
                    </button>
                </div>
            </section>
        </div>
    </div>
@endsection
