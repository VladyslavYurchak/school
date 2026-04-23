@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="mb-0">Створити тест</h1>

                <a href="{{ route('admin.testing.tests.index') }}" class="btn btn-outline-secondary">
                    Назад
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.testing.tests.store') }}" method="POST">
                        @include('admin.testing.tests._form')
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
