<table class="table table-bordered">
    <thead>
    <tr>
        <th>Викладач</th>
        <th>Поступлення за індив</th>
        <th>ЗП індив</th>
        <th>Чисте індив</th>
        <th>Поступлення за групи</th>
        <th>ЗП групи</th>
        <th>Чисте групи</th>
        <th>Всього прибуток</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($teachers as $teacher)
        @php
            $income = $incomeData[$teacher->id] ?? null;
        @endphp
        <tr>
            <td>{{ $teacher->last_name }} {{ $teacher->first_name }}</td>
            <td>{{ number_format($income['individualIncome'] ?? 0, 2, ',', ' ') }}</td>
            <td>{{ number_format($income['individualCosts'] ?? 0, 2, ',', ' ') }}</td>
            <td>{{ number_format($income['individualProfit'] ?? 0, 2, ',', ' ') }}</td>
            <td>{{ number_format($income['groupIncome'] ?? 0, 2, ',', ' ') }}</td>
            <td>{{ number_format($income['groupCosts'] ?? 0, 2, ',', ' ') }}</td>
            <td>{{ number_format($income['groupProfit'] ?? 0, 2, ',', ' ') }}</td>
            <td>{{ number_format($income['totalProfit'] ?? 0, 2, ',', ' ') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
