@extends('index.layouts.main')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Verify Your Email Address') }}</div>

                    <div class="card-body">
                        {{-- Новий підхід (Laravel 8+/UI): --}}
                        @if (session('status') === 'verification-link-sent')
                            <div class="alert alert-success" role="alert">
                                {{ __('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif

                        {{-- Старий підхід (Laravel 6/7/UI): --}}
                        @if (session('resent'))
                            <div class="alert alert-success" role="alert">
                                {{ __('A fresh verification link has been sent to your email address.') }}
                            </div>
                        @endif

                        <p>{{ __('Before proceeding, please check your email for a verification link.') }}</p>
                        <p>{{ __('If you did not receive the email') }},</p>

                        @if (Route::has('verification.send'))
                            {{-- Новий маршрут --}}
                            <form class="d-inline" method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 m-0 align-baseline">
                                    {{ __('click here to request another') }}
                                </button>
                            </form>
                        @elseif (Route::has('verification.resend'))
                            {{-- Старий маршрут --}}
                            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                                @csrf
                                <button type="submit" class="btn btn-link p-0 m-0 align-baseline">
                                    {{ __('click here to request another') }}
                                </button>
                            </form>
                        @else
                            <div class="text-muted">
                                {{ __('Resend route not found. Please check Auth::routes([\'verify\' => true]) in routes/web.php.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
