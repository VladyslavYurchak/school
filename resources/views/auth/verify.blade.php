@extends('public.layouts.main')

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <header class="auth-card-header">
                <span class="auth-card-icon">
                    <i class="bi bi-envelope-check" aria-hidden="true"></i>
                </span>
                <h1 class="auth-card-title">Підтвердіть email</h1>
                <p class="auth-card-text">Перевірте пошту та відкрийте посилання з листа, щоб активувати акаунт.</p>
            </header>

            @if (session('resent'))
                <div class="alert alert-success" role="alert">
                    Новий лист для підтвердження надіслано на вашу пошту.
                </div>
            @endif

            @if (Route::has('verification.resend'))
                <form method="POST" action="{{ route('verification.resend') }}">
                    @csrf
                    <button type="submit" class="btn-brand w-100">Надіслати лист ще раз</button>
                </form>
            @else
                <p class="account-empty text-center">Повторне надсилання листа зараз недоступне.</p>
            @endif
        </div>
    </div>
@endsection
