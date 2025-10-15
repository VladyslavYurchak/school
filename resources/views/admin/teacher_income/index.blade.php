@extends('admin.layouts.layout')

@section('content')
    <div class="container mt-4">
        <h2>Мої фінанси за {{ $selectedMonth }}/{{ $selectedYear }}</h2>

        {{-- Форма вибору місяця і року --}}
        <form method="GET" class="row g-3 mb-4">
            <div class="col-auto">
                <select name="month" class="form-select">
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" {{ $m == $selectedMonth ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <select name="year" class="form-select">
                    @foreach(range(now()->year - 2, now()->year + 1) as $y)
                        <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Показати</button>
            </div>
        </form>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                        <tr>
                            <th>Учень/Група</th>
                            <th>Індивідуальні заняття</th>
                            <th>Групові заняття</th>
                            <th>З індивідуальних</th>
                            <th>З групових</th>
                            <th>Всього</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($data as $row)
                            <tr>
                                <td>{{ $row['student']->full_name ?? '—' }}</td>
                                <td>{{ $row['individualCount'] }}</td>
                                <td>{{ $row['groupCount'] }}</td>
                                <td>{{ number_format($row['individualEarned'], 2) }} ₴</td>
                                <td>{{ number_format($row['groupEarned'], 2) }} ₴</td>
                                <td><strong>{{ number_format($row['totalEarned'], 2) }} ₴</strong></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Немає занять у цьому місяці.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
