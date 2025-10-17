@extends('admin.layouts.layout')

@section('title', 'Календар занять')

@section('content')
    <div class="container py-4">
        <div id="calendar"></div>
    </div>

    @include('admin.calendar.modals.add')
    @include('admin.calendar.modals.manage')
    @include('admin.calendar.modals.reschedule')
    @include('admin.calendar.modals.group-members')

@endsection

@push('styles')
    {{-- правильний шлях до CSS у версіях 6.x --}}
    <link href="https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/index.global.min.css" rel="stylesheet">
@endpush


@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    @include('admin.calendar.modals.calendar-script')
    @include('admin.calendar.modals.group-members-script')
@endpush
