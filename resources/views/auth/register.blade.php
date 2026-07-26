@extends('public.layouts.main')

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <header class="auth-card-header">
                <span class="auth-card-icon">
                    <i class="bi bi-person-plus" aria-hidden="true"></i>
                </span>
                <h1 class="auth-card-title">Реєстрація</h1>
                <p class="auth-card-text">Створіть акаунт для доступу до кабінету та онлайн-навчання.</p>
            </header>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <label for="name" class="form-label">Ім’я</label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        autocomplete="name"
                        required
                        autofocus
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control @error('password') is-invalid @enderror"
                        autocomplete="new-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label">Підтвердження пароля</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="btn-brand w-100">
                    <i class="bi bi-person-check" aria-hidden="true"></i>
                    Зареєструватися
                </button>
            </form>

            @include('auth.partials.social-login')

            <div class="auth-links">
                <span>Вже маєте акаунт? <a href="{{ route('login') }}">Увійти</a></span>
            </div>
        </div>
    </div>
@endsection
