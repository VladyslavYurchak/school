@extends('admin.layouts.layout')

@section('title', 'Загальний огляд')

@section('content')
    <div class="container"> {{-- Додали контейнер --}}
        <h1 class="mb-4">Загальний огляд</h1>

        <div class="mb-4">
            <ul class="nav nav-tabs" id="overviewTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance" aria-selected="true">
                        📊 Відвідуваність студентів
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary" type="button" role="tab" aria-controls="salary" aria-selected="false">
                        💼 Зарплата викладачів
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab" aria-controls="income" aria-selected="false">
                        💰 Місячний дохід
                    </button>
                </li>
            </ul>
        </div>

        <div class="tab-content" id="overviewTabsContent">
            <div class="tab-pane fade show active pt-4" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                @include('admin.data.partials.attendance-table')
            </div>
            <div class="tab-pane fade pt-4" id="salary" role="tabpanel" aria-labelledby="salary-tab">
                @include('admin.data.partials.salary-table')
            </div>

            <div class="tab-pane fade pt-4" id="income" role="tabpanel" aria-labelledby="income-tab">
                @include('admin.data.partials.income-table')
            </div>
        </div>
    </div>
@endsection

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="{{ asset('admin/data/student-calendar.js') }}"></script>
@endsection
