@extends('public.layouts.main')

@section('content')
    <div class="container py-5">
        <article class="post-show">
            <a href="{{ route('index') }}#posts-block" class="post-back-link">
                <i class="bi bi-arrow-left"></i>
                Повернутись на головну
            </a>

            <div class="post-show-header">
                <div class="post-date mb-2">{{ $post->created_at->format('d.m.Y') }}</div>
                <h1 class="post-show-title">{{ $post->title }}</h1>
            </div>

            @if($post->image)
                <div class="post-show-image-wrap">
                    <img src="{{ $post->image_url }}" class="post-show-image" alt="{{ $post->title }}">
                </div>
            @endif

            <div class="post-show-content">
                {!! nl2br(e($post->content)) !!}
            </div>
        </article>
    </div>
@endsection
