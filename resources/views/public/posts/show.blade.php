@extends('public.layouts.main')

@section('title', $post->title . ' | Корпорація Мов')
@section('description', \Illuminate\Support\Str::limit(strip_tags($post->content), 155))
@section('og_type', 'article')
@if($post->image_url)
    @section('image', $post->image_url)
@endif

@push('structured-data')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $post->title,
        'datePublished' => $post->created_at?->toAtomString(),
        'dateModified' => $post->updated_at?->toAtomString(),
        'mainEntityOfPage' => route('posts.show', $post),
        'image' => $post->image_url ?: asset(config('seo.default_image')),
        'author' => [
            '@type' => 'Organization',
            'name' => config('seo.business.name'),
            'url' => route('index'),
        ],
        'publisher' => [
            '@id' => route('index') . '#organization',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

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
