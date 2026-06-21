@extends('admin.layouts.layout')

@section('title', 'Календар занять')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-calendar-week"></i>
                            Викладач
                        </span>
                        <h1 class="admin-title">Мій розклад</h1>
                        <p class="admin-subtitle">Особистий календар занять, перенесень, скасувань і відміток відвідування.</p>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-body">
                    <div class="admin-calendar-shell">
                        <div id="calendar"></div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    @include('admin.calendar.modals.add')
    @include('admin.calendar.modals.manage')
    @include('admin.calendar.modals.edit')
    @include('admin.calendar.modals.reschedule')
    @include('admin.calendar.modals.group-members')
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js"></script>
    @include('admin.calendar.modals.calendar-script')
    @include('admin.calendar.modals.group-members-script')
@endpush
