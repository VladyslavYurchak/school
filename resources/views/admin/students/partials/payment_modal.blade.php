<!-- Модалка Оплата -->
<div class="modal fade" id="paymentModal{{ $student->id }}" tabindex="-1" aria-labelledby="paymentModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <form id="paymentForm{{ $student->id }}" method="POST" action="{{ route('admin.students.subscriptions.store', $student->id) }}">
            @csrf
            <input type="hidden" name="subscription_template_id" value="{{ $student->subscription_id ?? '' }}">
            <input type="hidden" name="month" id="selectedMonthInput{{ $student->id }}" required>
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel{{ $student->id }}">
                        Оплата абонементу для {{ $student->full_name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Тип оплати:</label>
                        <div>
                            <input type="radio" id="type-subscription-{{ $student->id }}" name="type" value="subscription" checked>
                            <label for="type-subscription-{{ $student->id }}">Абонемент</label>

                            <input type="radio" id="type-single-{{ $student->id }}" name="type" value="single" class="ms-3">
                            <label for="type-single-{{ $student->id }}">Поразова оплата</label>
                        </div>
                    </div>

                    <div id="subscriptionPayment{{ $student->id }}">
                        <div id="calendar{{ $student->id }}" class="month-grid">
                            @php
                                use Carbon\Carbon;

                                // Поточний місяць у Києві
                                $nowKyiv = Carbon::now('Europe/Kyiv');

                                // Починаємо з 2 місяців назад
                                $start = $nowKyiv->copy()->startOfMonth()->subMonths(2);

                                // Оплачені місяці для студента
                                $paid = $paidMonthsByStudent[$student->id] ?? [];
                            @endphp

                            @for ($i = 0; $i < 12; $i++)
                                @php
                                    $date = $start->copy()->addMonths($i);
                                    $monthStr = $date->format('Y-m');

                                    $isPaid = array_key_exists($monthStr, $paid);
                                    $paidPrice = $paid[$monthStr] ?? null;

                                    // Поточний місяць завжди жовтий
                                    $isCurrent = $monthStr === $nowKyiv->format('Y-m');
                                @endphp

                                <div
                                    class="month-box
                                        {{ $isPaid ? 'paid' : '' }}
                                        {{ $isCurrent ? 'current' : '' }}"
                                    data-month="{{ $monthStr }}"
                                    data-paid="{{ $isPaid ? '1' : '0' }}"
                                    onclick="selectMonth('{{ $student->id }}', '{{ $monthStr }}', this)"
                                >
                                    <div class="month-title">{{ $date->translatedFormat('F Y') }}</div>
                                    <div class="month-price text-muted small">
                                        {{ $paidPrice ? number_format($paidPrice, 0, ',', ' ') . ' грн' : '' }}
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div id="singlePayment{{ $student->id }}" style="display: none;">
                        <label for="singlePrice{{ $student->id }}" class="form-label">Сума поразової оплати (грн)</label>
                        <input
                            type="number"
                            name="price"
                            id="singlePrice{{ $student->id }}"
                            min="1"
                            step="1"
                            class="form-control"
                            placeholder="Введіть суму"
                        >
                        <button type="button" class="btn btn-primary mt-2" onclick="submitSinglePayment('{{ $student->id }}')">Оплатити</button>
                    </div>

                </div>
                <div class="modal-footer">
                    <span class="text-muted">Натисніть на місяць для оплати</span>
                </div>
            </div>
        </form>
    </div>
</div>
