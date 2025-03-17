@extends('index.layouts.main')

@section('content')
    <div class="card p-4">
        <h3 class="text-dark">{{ $post->id }}. {{ $post->title }}</h3>
        <p class="mt-3">{{ $post->content }}</p>

        @if($post->image)
            <img src="{{ asset('storage/' . $post->image) }}" class="img-fluid rounded mt-3" alt="Post Image">
        @endif

        <div class="mt-4">
            <a href="{{ route('posts.index') }}" class="btn btn-secondary">⬅ Повернутись</a>
        </div>
    </div>
@endsection
