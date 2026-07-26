@extends('public.layouts.main')

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <header class="auth-card-header">
                <span class="auth-card-icon">
                    <i class="bi bi-envelope" aria-hidden="true"></i>
                </span>
                <h1 class="auth-card-title">Відновлення пароля</h1>
                <p class="auth-card-text">Вкажіть email, і ми надішлемо посилання для створення нового пароля.</p>
            </header>

            @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="mb-4">
                    <label for="email" class="form-label">Email</label>
                    <input
                        id="email"
                        type="email"
                        class="form-control @error('email') is-invalid @enderror"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        autofocus
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-brand w-100">
                    Надіслати посилання
                </button>
            </form>

            <div class="auth-links">
                <a href="{{ route('login') }}">Повернутися до входу</a>
            </div>
        </div>
    </div>
@endsection
