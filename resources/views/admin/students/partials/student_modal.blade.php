<!-- Student Info Modal -->
<div class="modal fade" id="studentModal{{ $student->id }}" tabindex="-1"
     aria-labelledby="studentModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <div class="modal-header bg-light border-0">
                <h5 class="modal-title fw-semibold" id="studentModalLabel{{ $student->id }}">
                    {{ $student->full_name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>

            <div class="modal-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="fw-semibold text-secondary">Телефон:</span>
                        <span>{{ $student->phone ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="fw-semibold text-secondary">Email:</span>
                        <span>{{ $student->email ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="fw-semibold text-secondary">Дата народження:</span>
                        <span>{{ $student->birth_date ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="fw-semibold text-secondary">Контакт батьків:</span>
                        <span>{{ $student->parent_contact ?? '—' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span class="fw-semibold text-secondary">Дата початку:</span>
                        <span>{{ $student->start_date ?? '—' }}</span>
                    </li>
                    <li class="list-group-item">
                        <span class="fw-semibold text-secondary">Абонемент:</span><br>
                        @if($student->subscriptionTemplate)
                            <span class="ms-2">
                                {{ $student->subscriptionTemplate->title }}
                                ({{ $student->subscriptionTemplate->lessons_per_week }} р/т,
                                {{ $student->subscriptionTemplate->price }} грн)
                            </span>
                        @else
                            <span class="ms-2">—</span>
                        @endif
                    </li>
                    <li class="list-group-item">
                        <span class="fw-semibold text-secondary">Примітка:</span><br>
                        <span class="ms-2">{{ $student->note ?? '—' }}</span>
                    </li>
                </ul>
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Закрити</button>
            </div>
        </div>
    </div>
</div>
