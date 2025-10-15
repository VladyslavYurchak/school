<form id="salaryFilterForm" method="GET" class="mb-3 d-flex align-items-center gap-2">

    <label for="month" class="form-label mb-0">Місяць:</label>
    <select name="month" id="month" class="form-select" style="max-width: 150px;">

    @for ($m = 1; $m <= 12; $m++)
            <option value="{{ $m }}" {{ $m == $selectedMonth ? 'selected' : '' }}>
                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
            </option>
        @endfor
    </select>

    <label for="year" class="form-label mb-0">Рік:</label>
    <select name="year" id="year" class="form-select" style="max-width: 100px;">
        @for ($y = now()->year; $y >= 2022; $y--)
            <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
        @endfor
    </select>
</form>


<table class="table table-striped table-bordered">
    <thead>
    <tr>
        <th>Прізвище</th>
        <th>Ім'я</th>
        <th>Індивідуальні заняття</th>
        <th>Групові заняття</th>
        <th>Зарплата, грн</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($teachers as $teacher)
        <tr>
            <td>{{ $teacher->last_name }}</td>
            <td>{{ $teacher->first_name }}</td>
            <td>{{ $teacher->individualCount ?? 0 }}</td>
            <td>{{ $teacher->groupCount ?? 0 }}</td>
            <td>{{ number_format($teacher->salary ?? 0, 2, ',', ' ') }}</td>
        </tr>
    @empty
        <tr><td colspan="5">Дані відсутні.</td></tr>
    @endforelse
    </tbody>
</table>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('salaryFilterForm');
        form.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => form.submit());
        });
    });
</script>
