@extends('public.layouts.main')

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <header class="auth-card-header">
                <span class="auth-card-icon">
                    <i class="bi bi-person" aria-hidden="true"></i>
                </span>
                <h1 class="auth-card-title">Вхід</h1>
                <p class="auth-card-text">Увійдіть, щоб продовжити навчання.</p>
            </header>

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" @checked(old('remember'))>
                    <label class="form-check-label" for="remember">Запам’ятати мене</label>
                </div>

                <button type="submit" class="btn-brand w-100">
                    <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                    Увійти
                </button>
            </form>

            @include('auth.partials.social-login')

            <div class="auth-links">
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Забули пароль?</a>
                @endif
                <span>Немає акаунта? <a href="{{ route('register') }}">Зареєструватися</a></span>
            </div>
        </div>
    </div>
@endsection
