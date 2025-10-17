<!-- Модалка Оплата (уніфікований стиль) -->
<div class="modal fade" id="paymentModal{{ $student->id }}" tabindex="-1"
     aria-labelledby="paymentModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <form id="paymentForm{{ $student->id }}" method="POST"
              action="{{ route('admin.students.subscriptions.store', $student->id) }}">
            @csrf
            <input type="hidden" name="subscription_template_id" value="{{ $student->subscription_id ?? '' }}">
            <input type="hidden" name="month" id="selectedMonthInput{{ $student->id }}" required>

            <div class="modal-content shadow-lg border-0 rounded-3">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-semibold" id="paymentModalLabel{{ $student->id }}">
                        Оплата абонементу для {{ $student->full_name }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
                </div>

                <div class="modal-body">
                    {{-- Тип оплати --}}
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block mb-1">Тип оплати:</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       id="type-subscription-{{ $student->id }}" name="type"
                                       value="subscription" checked>
                                <label class="form-check-label" for="type-subscription-{{ $student->id }}">
                                    Абонемент
                                </label>
                            </div>

                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio"
                                       id="type-single-{{ $student->id }}" name="type" value="single">
                                <label class="form-check-label" for="type-single-{{ $student->id }}">
                                    Поразова оплата
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Оплата абонементу (місячна сітка) --}}
                    <div id="subscriptionPayment{{ $student->id }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="small text-muted">
                                Натисніть на місяць, щоб вибрати до оплати
                            </div>
                            <div class="d-flex align-items-center gap-3 small">
                                <span><span class="badge bg-warning text-dark me-1"> </span> поточний</span>
                                <span><span class="badge bg-success me-1"> </span> оплачено</span>
                            </div>
                        </div>

                        <div id="calendar{{ $student->id }}" class="month-grid">
                            @php
                                use Carbon\Carbon;
                                $nowKyiv = Carbon::now('Europe/Kyiv');
                                $start = $nowKyiv->copy()->startOfMonth()->subMonths(2);
                                $paid = $paidMonthsByStudent[$student->id] ?? [];
                            @endphp

                            @for ($i = 0; $i < 12; $i++)
                                @php
                                    $date = $start->copy()->addMonths($i);
                                    $monthStr = $date->format('Y-m');

                                    $isPaid = array_key_exists($monthStr, $paid);
                                    $paidPrice = $paid[$monthStr] ?? null;
                                    $isCurrent = $monthStr === $nowKyiv->format('Y-m');
                                @endphp

                                <div
                                    class="month-box {{ $isPaid ? 'paid' : '' }} {{ $isCurrent ? 'current' : '' }}"
                                    data-month="{{ $monthStr }}"
                                    data-paid="{{ $isPaid ? '1' : '0' }}"
                                    onclick="selectMonth('{{ $student->id }}', '{{ $monthStr }}', this)"
                                    role="button" tabindex="0"
                                    aria-label="Місяць {{ $date->translatedFormat('F Y') }} {{ $isPaid ? '(оплачено)' : '' }} {{ $isCurrent ? '(поточний)' : '' }}"
                                >
                                    <div class="month-title fw-semibold">{{ $date->translatedFormat('F Y') }}</div>
                                    <div class="month-price text-muted small">
                                        {{ $paidPrice ? number_format($paidPrice, 0, ',', ' ') . ' грн' : '' }}
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Поразова оплата --}}
                    <div id="singlePayment{{ $student->id }}" style="display:none;">
                        <label for="singlePrice{{ $student->id }}" class="form-label fw-semibold">
                            Сума поразової оплати (грн)
                        </label>
                        <input
                            type="number"
                            name="price"
                            id="singlePrice{{ $student->id }}"
                            min="1" step="1"
                            class="form-control"
                            placeholder="Введіть суму"
                            inputmode="numeric"
                        >
                        <button type="button" class="btn btn-primary mt-2"
                                onclick="submitSinglePayment('{{ $student->id }}')">
                            Оплатити
                        </button>
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <span class="text-muted me-auto small">Підтвердіть вибір місяця або введіть суму для поразової оплати</span>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрити</button>
                </div>
            </div>
        </form>
    </div>
</div>
