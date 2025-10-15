<!-- Modal -->
<div class="modal fade" id="studentModal{{ $student->id }}" tabindex="-1" aria-labelledby="studentModalLabel{{ $student->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentModalLabel{{ $student->id }}">
                    {{ $student->full_name }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрити"></button>
            </div>
            <div class="modal-body">
                <p><strong>Телефон:</strong> {{ $student->phone ?? '—' }}</p>
                <p><strong>Email:</strong> {{ $student->email ?? '—' }}</p>
                <p><strong>Дата народження:</strong> {{ $student->birth_date ?? '—' }}</p>
                <p><strong>Контакт батьків:</strong> {{ $student->parent_contact ?? '—' }}</p>
                <p><strong>Дата початку:</strong> {{ $student->start_date ?? '—' }}</p>
                <p><strong>Абонемент:</strong>
                    @if($student->subscriptionTemplate)
                        {{ $student->subscriptionTemplate->title }}
                        ({{ $student->subscriptionTemplate->lessons_per_week }} р/т)
                        ({{ $student->subscriptionTemplate->price }}грн)
                    @else
                        —
                    @endif                </p>
                <p><strong>Примітка:</strong> {{ $student->note ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>
