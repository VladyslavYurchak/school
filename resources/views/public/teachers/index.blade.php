@extends('public.layouts.main')

@section('content')
    <div class="teachers-page py-5">
        <div class="container">

            <section class="teachers-hero mb-5">
                <div class="teachers-hero-card">
                    <div class="row align-items-center g-4">
                        <div class="col-lg-8">
                            <span class="teachers-badge">Корпорація Мов</span>
                            <h1 class="teachers-page-title mb-3">Наші вчителі</h1>
                            <p class="teachers-page-text mb-0">
                                Познайомтесь із викладачами, які допоможуть вам вивчати мови впевнено,
                                із задоволенням та у комфортному для вас темпі.
                            </p>
                        </div>

                        <div class="col-lg-4">
                            <div class="teachers-hero-side">
                                <div class="teachers-hero-side-title">Навчання з підтримкою</div>
                                <div class="teachers-hero-side-text">
                                    Досвідчені викладачі, сучасні підходи та увага до вашого прогресу.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="teachers-list">
                @foreach($teachers as $teacher)
                    <article class="teacher-card">
                        <div class="row align-items-center g-4">

                            <div class="col-md-4 col-lg-3">
                                <div class="teacher-photo-column">
                                    <div class="teacher-photo-wrap">
                                        @if($teacher->public_photo)
                                            <img
                                                src="{{ asset('storage/' . $teacher->public_photo) }}"
                                                alt="{{ $teacher->full_name }}"
                                                class="teacher-photo"
                                            >
                                        @else
                                            <div class="teacher-photo teacher-photo-placeholder">
                                                <span>{{ mb_substr($teacher->full_name ?: 'В', 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    @if($teacher->public_details)
                                        <div class="teacher-details">
                                            @foreach(preg_split('/\r\n|\r|\n/', trim($teacher->public_details)) as $detail)
                                                @if(trim($detail) !== '')
                                                    <div class="teacher-detail-line">{{ trim($detail) }}</div>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="col-md-8 col-lg-9">
                                <div class="teacher-content">
                                    <div class="teacher-heading">
                                        <h2 class="teacher-name">
                                            {{ $teacher->full_name ?: 'Викладач' }}
                                        </h2>

                                        @if($teacher->public_position)
                                            <div class="teacher-position">
                                                - {{ ltrim($teacher->public_position, "- \t\n\r\0\x0B") }}
                                            </div>
                                        @endif
                                    </div>

                                    @if($teacher->public_bio)
                                        <div class="teacher-bio">
                                            @if($teacher->public_bio !== strip_tags($teacher->public_bio))
                                                {!! $teacher->public_bio !!}
                                            @else
                                                {!! nl2br(e($teacher->public_bio)) !!}
                                            @endif
                                        </div>
                                    @else
                                        <div class="teacher-bio teacher-bio-empty">
                                            Незабаром тут з’явиться коротка інформація про викладача.
                                        </div>
                                    @endif
                                </div>
                            </div>

                        </div>
                    </article>
                @endforeach
            </div>

        </div>
    </div>
@endsection
