@extends('public.layouts.main')

@section('content')
    @php
        $subscriptionTypeLabels = [
            'individual' => 'Індивідуальний',
            'group' => 'Груповий',
            'pair' => 'Парний',
        ];
    @endphp

    <div class="account-page">
        <div class="container">
            <header class="account-header">
                <div>
                    <h1 class="account-header-title">Оплата абонемента</h1>
                    <p class="account-header-text">Оберіть місяць навчання та перейдіть до захищеної оплати MonoPay.</p>
                </div>
                <a href="{{ route('student.dashboard') }}" class="btn-brand-outline">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i>
                    До кабінету
                </a>
            </header>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            @if($template)
                <div class="payment-layout">
                    <section class="account-panel">
                        <h2 class="account-panel-title">
                            <i class="bi bi-calendar-check" aria-hidden="true"></i>
                            Ваш абонемент
                        </h2>

                        <div class="payment-summary">
                            <div class="payment-summary-row">
                                <span class="payment-summary-label">Назва</span>
                                <span class="payment-summary-value">{{ $template->title }}</span>
                            </div>
                            <div class="payment-summary-row">
                                <span class="payment-summary-label">Тип</span>
                                <span class="payment-summary-value">{{ $subscriptionTypeLabels[$template->type] ?? $template->type }}</span>
                            </div>
                            <div class="payment-summary-row">
                                <span class="payment-summary-label">Занять на тиждень</span>
                                <span class="payment-summary-value">{{ $template->lessons_per_week }}</span>
                            </div>
                            <div class="payment-summary-row">
                                <span class="payment-summary-label">До сплати</span>
                                <span class="payment-summary-value">{{ number_format($template->price, 2, ',', ' ') }} грн</span>
                            </div>
                        </div>
                    </section>

                    <section class="account-panel payment-form-panel">
                        <h2 class="account-panel-title">
                            <i class="bi bi-credit-card" aria-hidden="true"></i>
                            Місяць оплати
                        </h2>

                        <form action="{{ route('student.payments.store') }}" method="POST">
                            @csrf

                            <div class="mb-4">
                                <label for="subscription_month" class="form-label">Оберіть місяць</label>
                                <select
                                    id="subscription_month"
                                    name="subscription_month"
                                    class="form-select"
                                    required
                                >
                                    @foreach($allowedPaymentMonths ?? [] as $paymentMonth)
                                        <option value="{{ $paymentMonth['value'] }}" @selected($paymentMonth['value'] === ($defaultPaymentMonth ?? now()->format('Y-m')))>
                                            {{ $paymentMonth['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-text mt-2 mb-0">
                                    Ви можете оплатити поточний місяць, два попередні або два наступні місяці.
                                    Уже оплачені місяці не створюються повторно.
                                </p>
                            </div>

                            <button type="submit" class="btn-brand w-100">
                                <i class="bi bi-shield-check" aria-hidden="true"></i>
                                Перейти до оплати
                            </button>
                        </form>
                    </section>
                </div>
            @else
                <section class="account-panel">
                    <h2 class="account-panel-title">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Абонемент ще не призначено
                    </h2>
                    <p class="account-empty">Зверніться до адміністратора школи, щоб обрати та закріпити абонемент.</p>
                </section>
            @endif
        </div>
    </div>
@endsection
