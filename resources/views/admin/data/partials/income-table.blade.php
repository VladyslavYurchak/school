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
            <th>Надходження</th><th>ЗП</th><th>Прибуток</th>
            <th>Надходження</th><th>ЗП</th><th>Прибуток</th>
            <th>Надходження</th><th>ЗП</th><th>Прибуток</th>
            <th>Надходження</th><th>ЗП</th><th>Прибуток</th>
        </tr>
        </thead>

        <tbody>
        @forelse ($reports as $row)
            <tr>
                <td>{{ $row['teacher']->full_name }}</td>
                <td>@money($row['rev_individual'])</td>
                <td>@money($row['cost_individual'])</td>
                <td>@money($row['inc_individual'])</td>

                <td>@money($row['rev_trial'])</td>
                <td>@money($row['cost_trial'])</td>
                <td>@money($row['inc_trial'])</td>

                <td>@money($row['rev_group'])</td>
                <td>@money($row['cost_group'])</td>
                <td>@money($row['inc_group'])</td>

                <td>@money($row['rev_pair'])</td>
                <td>@money($row['cost_pair'])</td>
                <td>@money($row['inc_pair'])</td>

                <td class="fw-semibold">@money($row['profit_total'])</td>
            </tr>
        @empty
            <tr><td colspan="14" class="text-center text-muted">Дані відсутні</td></tr>
        @endforelse
        </tbody>

        @if(!empty($reportTotals))
            <tfoot class="fw-semibold">
            <tr>
                <td>Разом</td>
                <td>@money($reportTotals['rev_ind'])</td>
                <td>@money($reportTotals['cost_ind'])</td>
                <td>@money($reportTotals['inc_ind'])</td>
                <td>@money($reportTotals['rev_trial'])</td>
                <td>@money($reportTotals['cost_trial'])</td>
                <td>@money($reportTotals['inc_trial'])</td>
                <td>@money($reportTotals['rev_grp'])</td>
                <td>@money($reportTotals['cost_grp'])</td>
                <td>@money($reportTotals['inc_grp'])</td>
                <td>@money($reportTotals['rev_pair'])</td>
                <td>@money($reportTotals['cost_pair'])</td>
                <td>@money($reportTotals['inc_pair'])</td>
                <td>@money($reportTotals['profit_total'])</td>
            </tr>
            </tfoot>
        @endif
    </table>
</div>
