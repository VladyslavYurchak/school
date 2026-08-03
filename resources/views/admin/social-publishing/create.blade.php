@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow"><i class="bi bi-share"></i> Соцмережі</span>
                        <h1 class="admin-title">Нова чернетка</h1>
                        <p class="admin-subtitle">Підготуйте один матеріал для вибраних платформ.</p>
                    </div>
                    <div class="admin-actions">
                        <a href="{{ route('admin.social-publishing.index') }}" class="admin-btn-soft">
                            <i class="bi bi-arrow-left"></i> До журналу
                        </a>
                    </div>
                </div>
            </section>

            <form action="{{ route('admin.social-publishing.store') }}"
                  method="POST"
                  enctype="multipart/form-data"
                  class="admin-panel admin-form admin-form-card">
                @csrf
                @include('admin.social-publishing._form')
            </form>
        </div>
    </div>
@endsection
