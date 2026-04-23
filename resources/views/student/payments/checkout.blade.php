@extends('index.layouts.main')

@section('content')
    <div class="container py-4">
        <h1 class="mb-4">Оплата через LiqPay</h1>

        <div class="card">
            <div class="card-body">
                <p><strong>Абонемент:</strong> {{ $template->title }}</p>
                <p><strong>Сума:</strong> {{ number_format($payment->amount, 2, ',', ' ') }} грн</p>

                {!! $form !!}
            </div>
        </div>
    </div>
@endsection
