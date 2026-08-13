@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-gear"></i>
                            Кабінет викладача
                        </span>
                        <h1 class="admin-title">Налаштування</h1>
                        <p class="admin-subtitle">Посилання на онлайн-заняття та Telegram-сповіщення.</p>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif

            @if(session('telegram_success'))
                <div class="alert alert-success" role="status">{{ session('telegram_success') }}</div>
            @endif

            @if(session('telegram_error'))
                <div class="alert alert-danger" role="alert">{{ session('telegram_error') }}</div>
            @endif

            <form method="POST" action="{{ route('teacher.settings.update') }}" class="admin-panel admin-form teacher-settings-panel">
                @csrf
                @method('PATCH')

                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">
                        <i class="bi bi-link-45deg"></i>
                        Zoom для занять
                    </h2>
                </div>

                <div class="admin-panel-body">
                    <div class="admin-form-section">
                        <label for="meeting_url" class="admin-form-label">Ваше постійне посилання на Zoom</label>
                        <input
                            type="url"
                            id="meeting_url"
                            name="meeting_url"
                            class="form-control @error('meeting_url') is-invalid @enderror"
                            value="{{ old('meeting_url', $teacher->meeting_url) }}"
                            maxlength="2048"
                            placeholder="https://zoom.us/j/..."
                            inputmode="url"
                        >
                        @error('meeting_url')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">
                            Це посилання буде доступне в Telegram для всіх ваших онлайн-занять. Окреме посилання в конкретному занятті матиме пріоритет.
                        </div>
                    </div>

                    <div class="admin-form-actions">
                        <button type="submit" class="admin-btn-primary">
                            <i class="bi bi-check2"></i>
                            Зберегти
                        </button>
                    </div>
                </div>
            </form>

            <section class="admin-panel teacher-settings-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">
                        <i class="bi bi-telegram"></i>
                        Telegram-сповіщення
                    </h2>
                </div>

                <div class="admin-panel-body">
                    @if($telegramAccount)
                        <p class="mb-3">
                            Telegram підключено
                            @if($telegramAccount->username)
                                як <strong>{{ '@'.$telegramAccount->username }}</strong>.
                            @endif
                            Ви отримуватимете нагадування про заняття та повідомлення, коли учень натисне «Не зможу бути».
                        </p>
                        <form method="POST" action="{{ route('teacher.telegram.disconnect') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-btn-soft">
                                <i class="bi bi-link-45deg"></i>
                                Від’єднати Telegram
                            </button>
                        </form>
                    @else
                        <p class="mb-3">
                            Підключіть Telegram, щоб отримувати нагадування про власні заняття та повідомлення про відсутність учнів.
                        </p>
                        <form method="POST" action="{{ route('teacher.telegram.connect') }}">
                            @csrf
                            <button type="submit" class="admin-btn-primary">
                                <i class="bi bi-telegram"></i>
                                Підключити Telegram
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
