@extends('index.layouts.main')

@section('content')
    <div class="d-flex justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="card p-4 shadow-lg" style="width: 400px; border-radius: 12px;">
            <h2 class="text-center mb-4">Вхід</h2>
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Запам'ятати мене</label>
                </div>

                <button type="submit" class="btn w-100" style="background-color: #2d6a4f; color: white;">Увійти</button>

                <div class="text-center mt-3">
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-decoration-none">Забули пароль?</a>
                    @endif
                    <p class="mt-2">Не маєте акаунту? <a href="{{ route('register') }}" class="text-decoration-none">Зареєструватися</a></p>
                </div>
            </form>
        </div>
    </div>
@endsection
