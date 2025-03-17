@extends('admin.layouts.layout')

@section('content')<!--begin::App Main-->
<main class="app-main">
    <!--begin::App Content Header-->
    <div class="card">
        <div class="card-header">
            <a href="{{ route('admin.post.index') }}" class="btn btn-secondary">Повернутися до списку постів</a>
        </div>
        <div class="card-body">
            <h3>{{ $post->title }}</h3>

            <p>{{ $post->content }}</p>

            @if($post->image)
                <div class="mb-3">
                    <img src="{{ $post->image }}" alt="Post Image" class="img-fluid" />
                </div>
            @endif
        </div>
    </div>
    <!--end::App Content-->
</main>
@endsection
