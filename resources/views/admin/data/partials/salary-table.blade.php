@php
    $sumCntInd=0; $sumCntTrial=0; $sumCntGrp=0; $sumCntPair=0;
    $sumCostTrial=0.0; $sumSalary=0.0;
@endphp

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
        @php
            $t = $row['teacher'];

            // для підсумків
            $sumCntInd   += $row['cnt_individual'];
            $sumCntTrial += $row['cnt_trial'];
            $sumCntGrp   += $row['cnt_group'];
            $sumCntPair  += $row['cnt_pair'];
            $sumSalary   += $row['salary_total'];
        @endphp
        <tr>
            <td>{{ $t->full_name }}</td>
            <td>{{ $row['cnt_individual'] }}</td>
            <td>{{ $row['cnt_trial'] }}</td>
            <td>{{ $row['cnt_group'] }}</td>
            <td>{{ $row['cnt_pair'] }}</td>
            <td>{{ number_format($row['salary_total'], 2, ',', ' ') }}</td>
        </tr>
    @empty
        <tr><td colspan="7">Дані відсутні.</td></tr>
    @endforelse
    </tbody>

    @if(count($reports))
        <tfoot>
        <tr class="fw-semibold">
            <td>Разом</td>
            <td>{{ $sumCntInd }}</td>
            <td>{{ $sumCntTrial }}</td>
            <td>{{ $sumCntGrp }}</td>
            <td>{{ $sumCntPair }}</td>
            <td>{{ number_format($sumSalary, 2, ',', ' ') }}</td>
        </tr>
        </tfoot>
    @endif
</table>
