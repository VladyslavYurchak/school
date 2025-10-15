@extends('admin.layouts.layout')

@section('content')
    <main class="app-main">
        <div class="container-fluid">
            <h2>Додати викладача</h2>

            @include('admin.teachers.form')
        </div>
    </main>
@endsection
