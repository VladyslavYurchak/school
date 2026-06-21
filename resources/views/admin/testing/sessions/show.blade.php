@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h1 class="mb-0">Сесія #{{ $session->id }}</h1>
                    <div class="text-muted small">
                        Мова: {{ $session->language_code }}
                    </div>
                </div>

                <a href="{{ route('admin.testing.sessions.index') }}" class="admin-btn-soft">
                    Назад
                </a>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-body">
                            <div class="text-muted small mb-1">Статус</div>
                            <div class="fw-semibold">
                                @if($session->status === 'completed' || $session->status === 'finished')
                                    <span class="badge text-bg-success">Завершено</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $session->status }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-body">
                            <div class="text-muted small mb-1">Загальний raw score</div>
                            <div class="fw-semibold">{{ $session->total_raw_score }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-body">
                            <div class="text-muted small mb-1">Weighted score</div>
                            <div class="fw-semibold">{{ $session->total_weighted_score }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-body">
                            <div class="text-muted small mb-1">Рівень</div>
                            <div class="fw-semibold">
                                {{ $session->detected_level ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-header">
                            <h3 class="admin-panel-title">Загальна інформація</h3>
                        </div>
                        <div class="admin-panel-body">
                            <div class="mb-2">
                                <strong>Початок:</strong>
                                {{ $session->started_at ? $session->started_at->format('d.m.Y H:i') : '—' }}
                            </div>

                            <div class="mb-2">
                                <strong>Завершення:</strong>
                                {{ $session->finished_at ? $session->finished_at->format('d.m.Y H:i') : '—' }}
                            </div>

                            <div class="mb-2">
                                <strong>IP:</strong>
                                {{ $session->ip_address ?? '—' }}
                            </div>

                            <div class="mb-2">
                                <strong>Max weighted score:</strong>
                                {{ $session->max_weighted_score }}
                            </div>

                            <div class="mb-0">
                                <strong>Result range:</strong>
                                {{ $session->resultRange?->title ?? '—' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-header">
                            <h3 class="admin-panel-title">Lead</h3>
                        </div>
                        <div class="admin-panel-body">
                            @if($session->lead)
                                <div class="mb-2">
                                    <strong>Ім’я:</strong>
                                    {{ $session->lead->name }}
                                </div>

                                <div class="mb-2">
                                    <strong>Телефон:</strong>
                                    {{ $session->lead->phone ?? '—' }}
                                </div>

                                <div class="mb-2">
                                    <strong>Email:</strong>
                                    {{ $session->lead->email ?? '—' }}
                                </div>

                                <div class="mb-2">
                                    <strong>Telegram:</strong>
                                    {{ $session->lead->telegram ?? '—' }}
                                </div>

                                <div class="mb-2">
                                    <strong>Вік:</strong>
                                    {{ $session->lead->age ?? '—' }}
                                </div>

                                <div class="mb-2">
                                    <strong>Формат навчання:</strong>
                                    {{ $session->lead->preferred_study_format ?? '—' }}
                                </div>

                                <div class="mb-0">
                                    <strong>Коментар:</strong>
                                    {{ $session->lead->notes ?? '—' }}
                                </div>
                            @else
                                <div class="text-muted">Lead ще не створено</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <section class="admin-panel">
                <div class="admin-panel-header">
                    <h3 class="admin-panel-title">Тести в межах сесії</h3>
                </div>
                <div class="admin-panel-body">
                    <div class="admin-table-wrap">
                    <table class="table admin-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тест</th>
                            <th>Статус</th>
                            <th>Raw score</th>
                            <th>Max score</th>
                            <th>Weighted</th>
                            <th>Вага тесту</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($session->attempts as $attempt)
                            <tr>
                                <td>{{ $attempt->id }}</td>
                                <td>{{ $attempt->test?->title ?? '—' }}</td>
                                <td>{{ $attempt->status }}</td>
                                <td>{{ $attempt->raw_score }}</td>
                                <td>{{ $attempt->max_score }}</td>
                                <td>{{ $attempt->weighted_score }}</td>
                                <td>{{ $attempt->test?->weight ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Attempts поки немає
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </section>

            @foreach($session->attempts as $attempt)
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <h3 class="admin-panel-title">
                            Відповіді: {{ $attempt->test?->title ?? 'Тест' }}
                        </h3>
                    </div>
                    <div class="admin-panel-body">
                        @forelse($attempt->answers as $answer)
                            <div class="border rounded p-3 mb-3">
                                <div class="mb-2">
                                    <strong>Питання:</strong>
                                </div>
                                <div class="mb-3">
                                    {{ $answer->question?->question_text ?? '—' }}
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <div class="text-muted small">Selected option ID</div>
                                        <div>{{ $answer->selected_option_id ?? '—' }}</div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="text-muted small">Answer text</div>
                                        <div>{{ $answer->answer_text ?? '—' }}</div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="text-muted small">Correct</div>
                                        <div>
                                            @if(is_null($answer->is_correct))
                                                —
                                            @elseif($answer->is_correct)
                                                Так
                                            @else
                                                Ні
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="text-muted small">Awarded points</div>
                                        <div>{{ $answer->awarded_points ?? '—' }}</div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="text-muted small">Question ID</div>
                                        <div>{{ $answer->question_id }}</div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted">Відповідей поки немає</div>
                        @endforelse
                    </div>
                </section>
            @endforeach

        </div>
    </div>
@endsection
