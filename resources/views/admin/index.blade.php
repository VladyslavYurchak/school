@extends('admin.layouts.layout')

@section('content')
    <div class="admin-page">
        <div class="admin-page-shell">
            <section class="admin-hero">
                <div class="admin-hero-content">
                    <div>
                        <span class="admin-eyebrow">
                            <i class="bi bi-speedometer2"></i>
                            {{ auth()->user()?->isAdmin() ? 'Адмін-панель' : 'Кабінет' }}
                        </span>
                        <h1 class="admin-title">
                            {{ auth()->user()?->isAdmin() ? 'Панель керування' : 'Кабінет викладача' }}
                        </h1>
                        <p class="admin-subtitle">
                            @if(auth()->user()?->isAdmin())
                                Нові заявки та швидкий огляд школи.
                            @else
                                Ваш робочий кабінет викладача.
                            @endif
                        </p>
                    </div>
                </div>
            </section>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(auth()->user()?->isAdmin())
                <section class="admin-panel">
                    <div class="admin-panel-header">
                        <h2 class="admin-panel-title">Заявки на безкоштовне заняття</h2>

                        @if(($newTrialLessonRequestsCount ?? 0) > 0)
                            <span class="admin-badge admin-badge-paid">
                                Нових: {{ $newTrialLessonRequestsCount }}
                            </span>
                        @endif
                    </div>

                    <div class="admin-panel-body p-0">
                        @if(($newTrialLessonRequests ?? collect())->count())
                            <div class="admin-table-wrap border-0 rounded-0">
                                <table class="table admin-table mb-0">
                                    <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Ім’я</th>
                                        <th>Контакт</th>
                                        <th>Коментар</th>
                                        <th class="text-end">Дія</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($newTrialLessonRequests as $trialRequest)
                                        <tr>
                                            <td>{{ $trialRequest->created_at->format('d.m.Y H:i') }}</td>
                                            <td class="fw-semibold">{{ $trialRequest->name }}</td>
                                            <td>
                                                <div>{{ $trialRequest->phone }}</div>
                                                @if($trialRequest->email)
                                                    <div class="small text-muted">{{ $trialRequest->email }}</div>
                                                @endif
                                                @if($trialRequest->preferred_contact)
                                                    <span class="admin-badge admin-badge-muted mt-1">
                                                        {{ $trialRequest->preferred_contact }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td>{{ $trialRequest->notes ?: '—' }}</td>
                                            <td class="text-end">
                                                <form method="POST" action="{{ route('admin.trial-lesson-requests.mark-contacted', $trialRequest) }}">
                                                    @csrf
                                                    <button type="submit" class="admin-btn-primary">
                                                        <i class="bi bi-check2"></i>
                                                        Оброблено
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="admin-empty-state">
                                <i class="bi bi-inbox"></i>
                                <h3>Нових заявок поки немає</h3>
                                <p>Коли хтось залишить заявку на сайті, вона з’явиться тут.</p>
                            </div>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
