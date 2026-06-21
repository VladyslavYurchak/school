@extends('public.layouts.main')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Підтвердіть email</div>

                    <div class="card-body">
                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                Новий лист для підтвердження надіслано на вашу пошту.
                            </div>
                        @endif

                        <p>Перед продовженням перевірте, будь ласка, вашу пошту.</p>
                        <p>Якщо лист не прийшов, ви можете надіслати його ще раз.</p>

                        @if (Route::has('verification.resend'))
                            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    Надіслати лист ще раз
                                </button>
                            </form>
                        @else
                            <div class="text-muted">
                                Повторне надсилання листа зараз недоступне.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
