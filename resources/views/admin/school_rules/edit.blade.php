@extends('admin.layouts.layout')

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">Редагувати правило</h1>
        @include('admin.school_rules.form', ['schoolRule' => $schoolRule])
    </div>
@endsection
