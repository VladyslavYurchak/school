@php
    $S = [
        'rev_ind'=>0,'rev_trial'=>0,'rev_grp'=>0,'rev_pair'=>0,
        'cost_ind'=>0,'cost_trial'=>0,'cost_grp'=>0,'cost_pair'=>0,
        'inc_ind'=>0,'inc_trial'=>0,'inc_grp'=>0,'inc_pair'=>0,
        'profit_total'=>0,
    ];
@endphp
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead>
        <tr>
            <th rowspan="2">Викладач</th>
            <th colspan="3" class="text-center">Індивідуальні</th>
            <th colspan="3" class="text-center">Пробні</th>
            <th colspan="3" class="text-center">Групові</th>
            <th colspan="3" class="text-center">Парні</th>
            <th rowspan="2">Всього прибуток</th>
        </tr>
        <tr>
            <th>Надходження</th>
            <th>ЗП</th>
            <th>Прибуток</th>
            <th></th>
            <th>ЗП</th>
            <th>Прибуток</th>
            <th>Надходження</th>
            <th>ЗП</th>
            <th>Прибуток</th>
            <th>Надходження</th>
            <th>ЗП</th>
            <th>Прибуток</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($reports as $row)
            @php
                $t = $row['teacher'];

                // для підсумків
                $S['rev_ind']   += $row['rev_individual'];
                $S['cost_ind']  += $row['cost_individual'];
                $S['inc_ind']   += $row['inc_individual'];

                $S['rev_trial'] += $row['rev_trial'];
                $S['cost_trial']+= $row['cost_trial'];
                $S['inc_trial'] += $row['inc_trial'];

                $S['rev_grp']   += $row['rev_group'];
                $S['cost_grp']  += $row['cost_group'];
                $S['inc_grp']   += $row['inc_group'];

                $S['rev_pair']  += $row['rev_pair'];
                $S['cost_pair'] += $row['cost_pair'];
                $S['inc_pair']  += $row['inc_pair'];

                $S['profit_total'] += $row['profit_total'];
            @endphp
            <tr>
                <td>{{ $t->full_name }}</td>

                <td>{{ number_format($row['rev_individual'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['cost_individual'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['inc_individual'], 2, ',', ' ') }}</td>

                <td>{{ number_format($row['rev_trial'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['cost_trial'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['inc_trial'], 2, ',', ' ') }}</td>

                <td>{{ number_format($row['rev_group'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['cost_group'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['inc_group'], 2, ',', ' ') }}</td>

                <td>{{ number_format($row['rev_pair'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['cost_pair'], 2, ',', ' ') }}</td>
                <td>{{ number_format($row['inc_pair'], 2, ',', ' ') }}</td>

                <td class="fw-semibold">{{ number_format($row['profit_total'], 2, ',', ' ') }}</td>
            </tr>
        @endforeach
        </tbody>

        @if(count($reports))
            <tfoot>
            <tr class="fw-semibold">
                <td>Разом</td>

                <td>{{ number_format($S['rev_ind'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['cost_ind'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['inc_ind'], 2, ',', ' ') }}</td>

                <td>{{ number_format($S['rev_trial'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['cost_trial'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['inc_trial'], 2, ',', ' ') }}</td>

                <td>{{ number_format($S['rev_grp'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['cost_grp'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['inc_grp'], 2, ',', ' ') }}</td>

                <td>{{ number_format($S['rev_pair'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['cost_pair'], 2, ',', ' ') }}</td>
                <td>{{ number_format($S['inc_pair'], 2, ',', ' ') }}</td>

                <td>{{ number_format($S['profit_total'], 2, ',', ' ') }}</td>
            </tr>
            </tfoot>
        @endif
    </table>
</div>

