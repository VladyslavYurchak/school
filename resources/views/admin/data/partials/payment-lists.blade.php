<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="h5 mb-0">Оплати навчання в школі</h3>
        <strong>{{ number_format($schoolPaymentsTotal ?? 0, 2, ',', ' ') }} UAH</strong>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
            <tr>
                <th>Учень</th>
                <th>Викладач</th>
                <th>Тип</th>
                <th>Абонемент</th>
                <th>Період</th>
                <th>Оплачено</th>
                <th>Джерело</th>
                <th class="text-end">Сума</th>
            </tr>
            </thead>
            <tbody>
            @forelse($schoolPayments as $payment)
                <tr>
                    <td>{{ $payment->student?->full_name ?? '-' }}</td>
                    <td>{{ $payment->student?->teacher?->full_name ?? '-' }}</td>
                    <td>{{ $payment->type }}</td>
                    <td>{{ $payment->subscriptionTemplate?->title ?? 'Разове індивідуальне заняття' }}</td>
                    <td>
                        {{ optional($payment->start_date)->format('d.m.Y') }}
                        -
                        {{ optional($payment->end_date)->format('d.m.Y') }}
                    </td>
                    <td>{{ optional($payment->paid_at)->format('d.m.Y H:i') ?? '-' }}</td>
                    <td>{{ $payment->payment?->provider ?? 'admin' }}</td>
                    <td class="text-end">{{ number_format($payment->price, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted">За цей період оплат навчання в школі немає.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3 class="h5 mb-0">Оплати курсів та онлайн-уроків</h3>
        <strong>{{ number_format($onlineProductPaymentsTotal ?? 0, 2, ',', ' ') }} UAH</strong>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead>
            <tr>
                <th>Учень</th>
                <th>Продукт</th>
                <th>Опис</th>
                <th>Оплачено</th>
                <th>Провайдер</th>
                <th class="text-end">Сума</th>
            </tr>
            </thead>
            <tbody>
            @forelse($onlineProductPayments as $payment)
                @php
                    $payload = is_array($payment->payload) ? $payment->payload : [];
                    $productType = isset($payload['course_id']) ? 'Курс' : 'Урок';
                @endphp
                <tr>
                    <td>{{ $payment->student?->full_name ?? '-' }}</td>
                    <td>{{ $productType }}</td>
                    <td>{{ $payment->description ?? '-' }}</td>
                    <td>{{ optional($payment->paid_at)->format('d.m.Y H:i') ?? '-' }}</td>
                    <td>{{ $payment->provider ?? '-' }}</td>
                    <td class="text-end">{{ number_format($payment->amount, 2, ',', ' ') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted">За цей період оплат курсів або онлайн-уроків немає.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
