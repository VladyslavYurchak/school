@extends('public.layouts.main')

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <header class="auth-card-header">
                <span class="auth-card-icon">
                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                </span>
                <h1 class="auth-card-title">Підтвердження пароля</h1>
                <p class="auth-card-text">З міркувань безпеки підтвердьте пароль перед продовженням.</p>
            </header>

            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf

                <div class="mb-4">
                    <label for="password" class="form-label">Пароль</label>
                    <input
                        id="password"
                        type="password"
                        class="form-control @error('password') is-invalid @enderror"
                        name="password"
                        autocomplete="current-password"
                        required
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn-brand w-100">Підтвердити</button>
            </form>

            @if (Route::has('password.request'))
                <div class="auth-links">
                    <a href="{{ route('password.request') }}">Забули пароль?</a>
                </div>
            @endif
        </div>
    </div>
@endsection
