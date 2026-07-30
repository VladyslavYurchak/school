@extends('public.layouts.main')

@section('content')
    @php
        $subscriptionStatusLabels = [
            'pending' => 'Очікує оплати',
            'active' => 'Активний',
            'expired' => 'Завершений',
            'cancelled' => 'Скасований',
        ];

        $paymentTypeLabels = [
            'subscription' => 'Абонемент',
            'single' => 'Разова оплата',
            'balance' => 'Поповнення балансу',
        ];

        $paymentStatusLabels = [
            'pending' => 'Очікує оплати',
            'paid' => 'Оплачено',
            'failed' => 'Неуспішно',
            'refunded' => 'Повернено',
        ];

        $lessonLogStatusLabels = [
            'completed' => 'Проведено',
            'charged' => 'Зараховано',
            'cancelled' => 'Скасовано',
            'rescheduled' => 'Перенесено',
            'absent' => 'Відсутній',
        ];

        $lessonTypeLabels = [
            'individual' => 'Індивідуальне',
            'group' => 'Групове',
            'pair' => 'Парне',
            'trial' => 'Пробне',
        ];
    @endphp

    <div class="account-page">
        <div class="container">
            @if(session('telegram_success'))
                <div class="alert alert-success" role="status">{{ session('telegram_success') }}</div>
            @endif

            @if(session('telegram_error'))
                <div class="alert alert-danger" role="alert">{{ session('telegram_error') }}</div>
            @endif

            <header class="account-header">
                <div>
                    <h1 class="account-header-title">Кабінет учня</h1>
                    <p class="account-header-text">Ваше навчання, курси та історія оплат в одному місці.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('student.vocabulary.learn') }}" class="btn-brand-outline">
                        <i class="bi bi-translate" aria-hidden="true"></i>
                        Мій словник
                    </a>
                    <a href="{{ route('student.payments.index') }}" class="btn-brand">
                        <i class="bi bi-credit-card" aria-hidden="true"></i>
                        Перейти до оплати
                    </a>
                </div>
            </header>

            <div class="row g-4">
            <div class="col-12">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-telegram" aria-hidden="true"></i>
                        Telegram-сповіщення
                    </h2>

                    @if($telegramAccount)
                        <p class="account-empty mb-3">
                            Telegram підключено
                            @if($telegramAccount->username)
                                як <strong>{{ '@'.$telegramAccount->username }}</strong>.
                            @else
                                до вашого кабінету.
                            @endif
                            Тут надходитимуть нагадування про заняття та оплату.
                        </p>

                        <form method="POST" action="{{ route('student.telegram.disconnect') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-brand-outline">
                                <i class="bi bi-link-45deg" aria-hidden="true"></i>
                                Від’єднати Telegram
                            </button>
                        </form>
                    @else
                        <p class="account-empty mb-3">
                            Підключіть Telegram, щоб отримувати нагадування про заняття та оплату.
                        </p>

                        <form method="POST" action="{{ route('student.telegram.connect') }}">
                            @csrf
                            <button type="submit" class="btn-brand">
                                <i class="bi bi-telegram" aria-hidden="true"></i>
                                Підключити Telegram
                            </button>
                        </form>
                    @endif
                </section>
            </div>

            <div class="col-lg-6">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-person-video3" aria-hidden="true"></i>
                        Мій викладач
                    </h2>

                    @if($teacher)
                        <dl class="account-details">
                            <div class="account-detail">
                                <dt>Ім’я</dt>
                                <dd>{{ $teacher->full_name }}</dd>
                            </div>
                            <div class="account-detail">
                                <dt>Телефон</dt>
                                <dd>{{ $teacher->phone ?: '—' }}</dd>
                            </div>
                            <div class="account-detail">
                                <dt>Email</dt>
                                <dd>{{ $teacher->email ?: ($teacher->user->email ?? '—') }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="account-empty">Викладача ще не призначено.</p>
                    @endif
                </section>
            </div>

            <div class="col-lg-6">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-calendar-check" aria-hidden="true"></i>
                        Мій абонемент
                    </h2>

                    @if($subscription)
                        <dl class="account-details mb-3">
                            <div class="account-detail">
                                <dt>Абонемент</dt>
                                <dd>{{ $subscription->subscriptionTemplate->title ?? 'Поразова оплата' }}</dd>
                            </div>
                            <div class="account-detail">
                                <dt>Статус</dt>
                                <dd>
                                    <span class="account-status account-status--{{ $subscription->status }}">
                                        {{ $subscriptionStatusLabels[$subscription->status] ?? $subscription->status }}
                                    </span>
                                </dd>
                            </div>
                            <div class="account-detail">
                                <dt>Період</dt>
                                <dd>{{ $subscription->start_date->format('d.m.Y') }} — {{ $subscription->end_date->format('d.m.Y') }}</dd>
                            </div>
                        </dl>
                        <a href="{{ route('student.payments.index') }}" class="btn-brand-outline">
                            Продовжити абонемент
                        </a>
                    @else
                        <p class="account-empty mb-3">Активного абонемента немає.</p>
                        <a href="{{ route('student.payments.index') }}" class="btn-brand-outline">
                            Перейти до оплати
                        </a>
                    @endif
                </section>
            </div>

            <div class="col-12">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-journal-bookmark" aria-hidden="true"></i>
                        Мої курси
                    </h2>

                    @if(($courses ?? collect())->count())
                        <div class="account-list">
                            @foreach($courses as $course)
                                <a href="{{ route('courses.show', $course) }}" class="account-list-link">
                                    <span class="account-list-title">{{ $course->title }}</span>
                                    <span class="account-list-meta">{{ $course->language->name ?? '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="account-empty">Оплачених курсів поки немає.</p>
                    @endif
                </section>
            </div>

            <div class="col-12">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-play-btn" aria-hidden="true"></i>
                        Мої окремі уроки
                    </h2>

                    @if(($lessons ?? collect())->count())
                        <div class="account-list">
                            @foreach($lessons as $lesson)
                                <a href="{{ route('courses.lessons.show', [$lesson->course, $lesson]) }}" class="account-list-link">
                                    <span class="account-list-title">{{ $lesson->title }}</span>
                                    <span class="account-list-meta">{{ $lesson->course->title ?? '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="account-empty">Окремо оплачених уроків поки немає.</p>
                    @endif
                </section>
            </div>

            <div class="col-12">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-calendar-event" aria-hidden="true"></i>
                        Найближчі заняття
                    </h2>

                    @if($upcomingLessons->count())
                        <div class="account-table-wrap">
                            <table class="account-table">
                                <thead>
                                <tr>
                                    <th>Дата і час</th>
                                    <th>Заняття</th>
                                    <th>Формат</th>
                                    <th>Викладач</th>
                                    <th>Група</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($upcomingLessons as $lesson)
                                    <tr>
                                        <td data-label="Дата і час">
                                            <strong>{{ $lesson->start_date->format('d.m.Y') }}</strong>
                                            <span class="account-table-secondary">
                                                {{ $lesson->start_date->format('H:i') }}
                                                @if($lesson->end_date)
                                                    — {{ $lesson->end_date->format('H:i') }}
                                                @endif
                                            </span>
                                        </td>
                                        <td data-label="Заняття">{{ $lesson->title }}</td>
                                        <td data-label="Формат">
                                            <span class="account-status account-status--planned">
                                                {{ $lessonTypeLabels[$lesson->lesson_type->value] ?? $lesson->lesson_type->value }}
                                            </span>
                                        </td>
                                        <td data-label="Викладач">{{ $lesson->teacher?->full_name ?: '—' }}</td>
                                        <td data-label="Група">{{ $lesson->group?->name ?: 'Особисто' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="account-empty">Найближчих запланованих занять поки немає.</p>
                    @endif
                </section>
            </div>

            <div class="col-12">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                        Мої заняття
                    </h2>

                    @if($lessonLogs->count())
                        <div class="account-table-wrap">
                            <table class="account-table">
                                    <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Тип</th>
                                        <th>Статус</th>
                                        <th>Коментар</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($lessonLogs as $log)
                                        <tr>
                                            <td data-label="Дата">{{ \Carbon\Carbon::parse($log->date)->format('d.m.Y') }}</td>
                                            <td data-label="Тип">{{ $lessonTypeLabels[$log->lesson_type] ?? ($log->lesson_type ?? '—') }}</td>
                                            <td data-label="Статус">{{ $lessonLogStatusLabels[$log->status] ?? ($log->status ?? '—') }}</td>
                                            <td data-label="Коментар">{{ $log->comment ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                            </table>
                        </div>
                    @else
                        <p class="account-empty">Занять поки немає.</p>
                    @endif
                </section>
            </div>

            <div class="col-12">
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-receipt" aria-hidden="true"></i>
                        Мої оплати
                    </h2>

                    @if($payments->count())
                        <div class="account-table-wrap">
                            <table class="account-table">
                                    <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Сума</th>
                                        <th>Тип</th>
                                        <th>Статус</th>
                                        <th>Опис</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td data-label="Дата">{{ optional($payment->paid_at)->format('d.m.Y H:i') ?: $payment->created_at->format('d.m.Y H:i') }}</td>
                                            <td data-label="Сума">{{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                            <td data-label="Тип">{{ $paymentTypeLabels[$payment->type] ?? $payment->type }}</td>
                                            <td data-label="Статус">
                                                <span class="account-status account-status--{{ $payment->status }}">
                                                    {{ $paymentStatusLabels[$payment->status] ?? $payment->status }}
                                                </span>
                                            </td>
                                            <td data-label="Опис">{{ $payment->description ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                            </table>
                        </div>
                    @else
                        <p class="account-empty">Оплат поки немає.</p>
                    @endif
                </section>
            </div>
        </div>
        </div>
    </div>
@endsection
