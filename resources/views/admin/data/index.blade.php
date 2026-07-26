@extends('admin.layouts.layout')

@section('title', 'Загальний огляд')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
@endsection

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell admin-data-page">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-bar-chart"></i>
                            Адмін
                        </span>
                        <h1 class="admin-title">Дані</h1>
                        <p class="admin-subtitle">Відвідуваність, зарплати викладачів, дохід школи та журнали оплат.</p>
                    </div>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Період</h2>
                </div>

                <div class="admin-panel-body">
                    <form id="attendanceFilterForm" method="GET" class="admin-filter-grid admin-filter-grid-three">
                        <div class="admin-field">
                            <label for="month">Місяць</label>
                            <select name="month" id="month" class="form-select">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ $m }}" {{ request('month', now()->month) == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <div class="admin-field">
                            <label for="year">Рік</label>
                            <select name="year" id="year" class="form-select">
                                @for ($y = now()->year + 1; $y >= 2022; $y--)
                                    <option value="{{ $y }}" {{ request('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                    </form>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h2 class="admin-panel-title">Огляд</h2>
                </div>

                <div class="admin-panel-body">
                    <ul class="nav nav-tabs admin-tabs" id="overviewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance" aria-selected="true">
                                <i class="bi bi-calendar-check"></i>
                                Відвідуваність студентів
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="salary-tab" data-bs-toggle="tab" data-bs-target="#salary" type="button" role="tab" aria-controls="salary" aria-selected="false">
                                <i class="bi bi-briefcase"></i>
                                Зарплата викладачів
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="income-tab" data-bs-toggle="tab" data-bs-target="#income" type="button" role="tab" aria-controls="income" aria-selected="false">
                                <i class="bi bi-cash-coin"></i>
                                Місячний дохід
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                                <i class="bi bi-receipt"></i>
                                Оплати
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="overviewTabsContent">
                        <div class="tab-pane fade show active" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                            @include('admin.data.partials.attendance-table')
                        </div>
                        <div class="tab-pane fade" id="salary" role="tabpanel" aria-labelledby="salary-tab">
                            @include('admin.data.partials.salary-table')
                        </div>
                        <div class="tab-pane fade" id="income" role="tabpanel" aria-labelledby="income-tab">
                            @include('admin.data.partials.income-table')
                        </div>
                        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                            @include('admin.data.partials.payment-lists')
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('attendanceFilterForm');

            form?.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', () => form.submit());
            });
        });
    </script>
@endsection
