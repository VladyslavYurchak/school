@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h1 class="mb-0">Сесії тестування</h1>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Мова</th>
                            <th>Статус</th>
                            <th>Бал</th>
                            <th>Макс</th>
                            <th>Рівень</th>
                            <th>Початок</th>
                            <th class="text-end">Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($sessions as $session)
                            <tr>
                                <td>{{ $session->id }}</td>

                                <td>{{ $session->language_code }}</td>

                                <td>
                                    @if($session->status === 'completed' || $session->status === 'finished')
                                        <span class="badge text-bg-success">Завершено</span>
                                    @else
                                        <span class="badge text-bg-secondary">В процесі</span>
                                    @endif
                                </td>

                                <td>
                                    <strong>{{ $session->total_weighted_score }}</strong>
                                </td>

                                <td>{{ $session->max_weighted_score }}</td>

                                <td>
                                    @if($session->detected_level)
                                        <span class="badge text-bg-primary">
                                            {{ $session->detected_level }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $session->started_at
                                        ? \Carbon\Carbon::parse($session->started_at)->format('d.m.Y H:i')
                                        : '—' }}
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('admin.testing.sessions.show', $session) }}"
                                       class="btn btn-sm btn-primary">
                                        Переглянути
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Сесій поки немає
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{ $sessions->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
