@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h1 class="mb-1">Панель керування</h1>
                    <p class="text-muted mb-0">
                        @if(auth()->user()?->isAdmin())
                            Нові заявки та швидкий огляд школи.
                        @else
                            Ваш робочий кабінет викладача.
                        @endif
                    </p>
                </div>
            </div>

            @if(auth()->user()?->isAdmin())
                <div class="row g-3">
                    <div class="col-12 col-xl-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h2 class="h5 mb-0">Заявки на безкоштовне заняття</h2>

                                @if(($newTrialLessonRequestsCount ?? 0) > 0)
                                    <span class="badge text-bg-danger">
                                        Нових: {{ $newTrialLessonRequestsCount }}
                                    </span>
                                @endif
                            </div>

                            <div class="card-body p-0">
                                @if(($newTrialLessonRequests ?? collect())->count())
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
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
                                                    <td>{{ $trialRequest->name }}</td>
                                                    <td>
                                                        <div>{{ $trialRequest->phone }}</div>
                                                        @if($trialRequest->email)
                                                            <div class="small text-muted">{{ $trialRequest->email }}</div>
                                                        @endif
                                                        @if($trialRequest->preferred_contact)
                                                            <span class="badge text-bg-light">
                                                                {{ $trialRequest->preferred_contact }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $trialRequest->notes ?: '—' }}</td>
                                                    <td class="text-end">
                                                        <form method="POST" action="{{ route('admin.trial-lesson-requests.mark-contacted', $trialRequest) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">
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
                                    <div class="p-4 text-muted">
                                        Нових заявок поки немає.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
