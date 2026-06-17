@extends('public.layouts.main')

@section('content')
    @php
        $subscriptionStatusLabels = [
            'pending' => 'Очікує оплати',
            'active' => 'Активний',
            'expired' => 'Завершений',
            'cancelled' => 'Скасований',
        ];

        $paymentTypeLabels = [
            'subscription' => 'Абонемент',
            'single' => 'Разова оплата',
            'balance' => 'Поповнення балансу',
        ];

        $paymentStatusLabels = [
            'pending' => 'Очікує оплати',
            'paid' => 'Оплачено',
            'failed' => 'Неуспішно',
            'refunded' => 'Повернено',
        ];

        $lessonTypeLabels = [
            'individual' => 'Індивідуальне',
            'group' => 'Групове',
            'pair' => 'Парне',
            'trial' => 'Пробне',
        ];
    @endphp

    <div class="container py-4">
        <h1 class="mb-4">Кабінет учня</h1>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Мій викладач</h4>

                        @if($teacher)
                            <p class="mb-2"><strong>Ім’я:</strong> {{ $teacher->full_name }}</p>
                            <p class="mb-2"><strong>Телефон:</strong> {{ $teacher->phone ?: '—' }}</p>
                            <p class="mb-2"><strong>Email:</strong> {{ $teacher->email ?: ($teacher->user->email ?? '—') }}</p>
                            <p class="mb-0"><strong>Нотатка:</strong> {{ $teacher->note ?: '—' }}</p>
                        @else
                            <p class="mb-0">Викладача ще не призначено.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Мій абонемент</h4>

                        @if($subscription)
                            <p class="mb-2">
                                <strong>Абонемент:</strong>
                                {{ $subscription->subscriptionTemplate->title ?? 'Поразова оплата' }}
                            </p>
                            <p class="mb-2"><strong>Статус:</strong> {{ $subscriptionStatusLabels[$subscription->status] ?? $subscription->status }}</p>
                            <p class="mb-2"><strong>Період:</strong> {{ $subscription->start_date->format('d.m.Y') }} — {{ $subscription->end_date->format('d.m.Y') }}</p>
                            <a href="{{ route('student.payments.index') }}" class="btn btn-primary">
                                Оплатити абонемент
                            </a>
                        @else
                            <p class="mb-3">Активного абонемента немає.</p>

                            <a href="{{ route('student.payments.index') }}" class="btn btn-primary">
                                Перейти до оплати
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Мої курси</h4>

                        @if(($courses ?? collect())->count())
                            <div class="list-group">
                                @foreach($courses as $course)
                                    <a href="{{ route('courses.show', $course) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>{{ $course->title }}</span>
                                        <span class="badge text-bg-primary">{{ $course->language->name ?? '' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="mb-0">Оплачених курсів поки немає.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Мої окремі уроки</h4>

                        @if(($lessons ?? collect())->count())
                            <div class="list-group">
                                @foreach($lessons as $lesson)
                                    <a href="{{ route('courses.lessons.show', [$lesson->course, $lesson]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>{{ $lesson->title }}</span>
                                        <span class="badge text-bg-secondary">{{ $lesson->course->title ?? '' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="mb-0">Окремо оплачених уроків поки немає.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Мої заняття</h4>

                        @if($lessonLogs->count())
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Тип</th>
                                        <th>Статус</th>
                                        <th>Коментар</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($lessonLogs as $log)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($log->date)->format('d.m.Y') }}</td>
                                            <td>{{ $lessonTypeLabels[$log->lesson_type] ?? ($log->lesson_type ?? '—') }}</td>
                                            <td>{{ $paymentStatusLabels[$log->status] ?? ($log->status ?? '—') }}</td>
                                            <td>{{ $log->comment ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">Занять поки немає.</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3">Мої оплати</h4>

                        @if($payments->count())
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>Дата</th>
                                        <th>Сума</th>
                                        <th>Тип</th>
                                        <th>Статус</th>
                                        <th>Опис</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ optional($payment->paid_at)->format('d.m.Y H:i') ?: $payment->created_at->format('d.m.Y H:i') }}</td>
                                            <td>{{ number_format($payment->amount, 2, ',', ' ') }} {{ $payment->currency }}</td>
                                            <td>{{ $paymentTypeLabels[$payment->type] ?? $payment->type }}</td>
                                            <td>{{ $paymentStatusLabels[$payment->status] ?? $payment->status }}</td>
                                            <td>{{ $payment->description ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="mb-0">Оплат поки немає.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
