@extends('index.layouts.main')

@section('content')
    <style>
        :root {
            --dark-green: #2d6a4f;
            --black: #000;
            --white: #fff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .register-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 100px); /* Піднімаємо форму ближче до navbar */
            padding: 20px;
        }

        .register-container {
            background-color: var(--white);
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            text-align: center;
            border: 3px solid var(--black);
        }

        .register-container h2 {
            margin-bottom: 20px;
            color: var(--black);
        }

        .register-container input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 2px solid var(--black);
            border-radius: 6px;
            font-size: 1em;
            background-color: #f9f9f9;
        }

        .register-container input:focus {
            outline: none;
            border-color: var(--dark-green);
        }

        .btn-dark-green {
            background-color: var(--dark-green);
            color: var(--white);
            font-weight: bold;
            padding: 12px;
            border: 2px solid var(--black);
            border-radius: 6px;
            width: 100%;
            font-size: 1em;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-dark-green:hover {
            background-color: var(--black);
            color: var(--white);
        }

        .form-footer {
            margin-top: 15px;
            font-size: 0.9em;
        }

        .form-footer a {
            color: var(--dark-green);
            text-decoration: none;
            font-weight: bold;
            transition: 0.3s;
        }

        .form-footer a:hover {
            color: var(--black);
        }

        @media (max-width: 450px) {
            .register-container {
                padding: 20px;
            }
            .register-container input {
                padding: 10px;
            }
            .btn-dark-green {
                padding: 10px;
            }
        }
    </style>

    <div class="register-wrapper">
        <div class="register-container">
            <h2>Реєстрація</h2>
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-3">
                    <input id="name" type="text" name="name" placeholder="Ім'я" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required autofocus>
                    @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <input id="email" type="email" name="email" placeholder="Email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <input id="password" type="password" name="password" placeholder="Пароль" class="form-control @error('password') is-invalid @enderror" required>
                    @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Підтвердження пароля" class="form-control" required>
                </div>

                <button type="submit" class="btn-dark-green">Зареєструватися</button>
            </form>

            <div class="form-footer">
                <p>Вже маєте акаунт? <a href="{{ route('login') }}">Повернутись до авторизації</a></p>
            </div>
        </div>
    </div>
@endsection
