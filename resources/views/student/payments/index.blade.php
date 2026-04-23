@extends('index.layouts.main')

@section('content')
    <div class="container py-4">
        <h1 class="mb-4">Оплата</h1>

        <div class="card">
            <div class="card-body">

                @if($template)
                    <h4 class="mb-3">Ваш закріплений абонемент</h4>

                    <p><strong>Назва:</strong> {{ $template->title }}</p>
                    <p><strong>Тип:</strong> {{ $template->type }}</p>
                    <p><strong>Занять на тиждень:</strong> {{ $template->lessons_per_week }}</p>
                    <p><strong>Ціна:</strong> {{ number_format($template->price, 2, ',', ' ') }} грн</p>

                    <form action="{{ route('student.payments.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="subscription_template_id" value="{{ $template->id }}">

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
