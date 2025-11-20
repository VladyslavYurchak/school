@if($payments->isEmpty())
    <div class="text-muted small">Немає поразових оплат за обраний період.</div>
@else
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
            <tr>
                <th style="width: 140px;">Дата</th>
                <th>Сума, грн</th>
                <th style="width: 120px;" class="text-end">Дії</th>
            </tr>
            </thead>
            <tbody>
            @foreach($payments as $p)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($p->start_date)->translatedFormat('d.m.Y') }}</td>
                    <td>{{ number_format($p->price, 0, ',', ' ') }}</td>
                    <td class="text-end">
                        <form method="POST" action="{{ route('admin.students.subscriptions.single.destroy', [$student->id, $p->id]) }}"
                              onsubmit="return confirm('Скасувати цю оплату?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                Скасувати
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
