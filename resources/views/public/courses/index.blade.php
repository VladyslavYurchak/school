@extends('public.layouts.main')

@section('content')
    <div class="container py-4">
        <div class="d-flex align-items-center mb-4">
            <h1 class="mb-0">Курси</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-4">
            @forelse($courses as $course)
                <div class="col-md-6 col-xl-4">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <h2 class="h5 mb-0">{{ $course->title }}</h2>
                                @php
                                    $hasAccess = $course->isAvailableFor(auth()->user());
                                @endphp

                                <span class="badge text-bg-{{ $hasAccess ? 'success' : ($course->isPaid() ? 'warning' : 'success') }}">
                                 @if($hasAccess)
                                        Доступ відкрито
                                    @elseif($course->isPaid())
                                        {{ number_format($course->price, 0, ',', ' ') }} грн
                                    @else
                                        Безкоштовно
                                    @endif
                                </span>
                            </div>

                            <p class="text-muted mb-2">{{ $course->language->name ?? '' }}</p>
                            <p class="flex-grow-1">{{ \Illuminate\Support\Str::limit($course->description, 140) }}</p>
                            <p class="small text-muted mb-3">Уроків: {{ $course->lessons_count }}</p>

                            <a href="{{ route('courses.show', $course) }}" class="btn btn-primary mt-auto">Переглянути</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <p class="mb-0">Поки немає опублікованих курсів.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $courses->links() }}
        </div>
    </div>
@endsection
