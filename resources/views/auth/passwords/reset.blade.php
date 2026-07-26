@extends('public.layouts.main')

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <header class="auth-card-header">
                <span class="auth-card-icon">
                    <i class="bi bi-key" aria-hidden="true"></i>
                </span>
                <h1 class="auth-card-title">Новий пароль</h1>
                <p class="auth-card-text">Створіть новий пароль для свого акаунта.</p>
            </header>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        name="email"
                        value="{{ $email ?? old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Новий пароль</label>
                    <input
                        id="password"
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password-confirm" class="form-label">Підтвердження пароля</label>
                    <input
                        id="password-confirm"
                        type="password"
                        class="form-control"
                        name="password_confirmation"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="btn-brand w-100">Зберегти новий пароль</button>
            </form>
        </div>
    </div>
@endsection
