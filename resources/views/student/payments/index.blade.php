@extends('public.layouts.main')

@section('content')
    @php
        $subscriptionTypeLabels = [
            'individual' => 'Індивідуальний',
            'group' => 'Груповий',
            'pair' => 'Парний',
        ];
    @endphp

    <div class="container py-4">
        <h1 class="mb-4">Оплата</h1>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="card">
            <div class="card-body">

                @if($template)
                    <h4 class="mb-3">Ваш закріплений абонемент</h4>

                    <p><strong>Назва:</strong> {{ $template->title }}</p>
                    <p><strong>Тип:</strong> {{ $subscriptionTypeLabels[$template->type] ?? $template->type }}</p>
                    <p><strong>Занять на тиждень:</strong> {{ $template->lessons_per_week }}</p>
                    <p><strong>Ціна:</strong> {{ number_format($template->price, 2, ',', ' ') }} грн</p>

                    <form action="{{ route('student.payments.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="subscription_month" class="form-label">Місяць оплати</label>
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
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Оплатити
                        </button>
                    </form>

                @else
                    <p class="mb-0">Для вас ще не закріплено абонемент. Зверніться до адміністратора.</p>
                @endif

            </div>
        </div>
    </div>
@endsection
