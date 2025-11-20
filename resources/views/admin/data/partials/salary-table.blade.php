<table class="table table-striped table-bordered align-middle">
    <thead>
    <tr>
        <th>Викладач</th>
        <th>Індивідуальні</th>
        <th>Пробні</th>
        <th>Групові</th>
        <th>Парні</th>
        <th>Зарплата, грн</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($reports as $row)
        <tr>
            <td>{{ $row['teacher']->full_name }}</td>
            <td>{{ $row['cnt_individual'] }}</td>
            <td>{{ $row['cnt_trial'] }}</td>
            <td>{{ $row['cnt_group'] }}</td>
            <td>{{ $row['cnt_pair'] }}</td>
            <td>{{ number_format($row['salary_total'], 2, ',', ' ') }}</td>
        </tr>
    @empty
        <tr><td colspan="6">Дані відсутні.</td></tr>
    @endforelse
    </tbody>

    @isset($reportTotals)
        <tfoot>
        <tr class="fw-semibold">
            <td>Разом</td>
            <td>{{ $reportTotals['cnt_individual'] }}</td>
            <td>{{ $reportTotals['cnt_trial'] }}</td>
            <td>{{ $reportTotals['cnt_group'] }}</td>
            <td>{{ $reportTotals['cnt_pair'] }}</td>
            <td>{{ number_format($reportTotals['salary_total'], 2, ',', ' ') }}</td>
        </tr>
        </tfoot>
    @endisset
</table>
