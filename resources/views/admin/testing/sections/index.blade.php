@extends('admin.layouts.layout')

@section('content')
    <div class="app-content p-3">
        <div class="container-fluid">

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h1 class="mb-0">Секції тесту: {{ $test->title }}</h1>
                    <div class="text-muted small">Мова: {{ strtoupper($test->language_code) }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.testing.tests.index') }}" class="btn btn-outline-secondary">
                        До тестів
                    </a>

                    <a href="{{ route('admin.testing.tests.questions.index', $test) }}" class="btn btn-outline-secondary">
                        Усі питання тесту
                    </a>

                    <a href="{{ route('admin.testing.tests.sections.create', $test) }}" class="btn btn-custom">
                        Створити секцію
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive">
                    @php
                        $typeLabels = [
                            'grammar' => 'Grammar',
                            'reading' => 'Reading',
                            'listening' => 'Listening',
                        ];

                        $mediaLabels = [
                            'none' => 'Без медіа',
                            'youtube' => 'YouTube',
                            'audio' => 'Audio',
                            'text' => 'Текст',
                        ];
                    @endphp

                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Порядок</th>
                            <th>Назва</th>
                            <th>Тип</th>
                            <th>Медіа</th>
                            <th>Активна</th>
                            <th class="text-end">Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($sections as $section)
                            <tr>
                                <td>{{ $section->id }}</td>
                                <td>{{ $section->sort_order }}</td>

                                <td>
                                    <div class="fw-semibold">{{ $section->title }}</div>

                                    @if($section->description)
                                        <div class="small text-muted">
                                            {{ \Illuminate\Support\Str::limit(strip_tags($section->description), 100) }}
                                        </div>
                                    @endif

                                    @if($section->instruction_text)
                                        <div class="small mt-1">
                                            <span class="text-muted">Інструкція:</span>
                                            {{ \Illuminate\Support\Str::limit(strip_tags($section->instruction_text), 100) }}
                                        </div>
                                    @endif
                                </td>

                                <td>
                                    {{ $typeLabels[$section->type] ?? $section->type }}
                                </td>

                                <td>
                                    @if(($section->media_type ?? 'none') !== 'none')
                                        <div>{{ $mediaLabels[$section->media_type] ?? $section->media_type }}</div>

                                        @if($section->media_title)
                                            <div class="small text-muted">
                                                {{ $section->media_title }}
                                            </div>
                                        @endif

                                        @if($section->media_url)
                                            <div class="small text-muted">
                                                {{ \Illuminate\Support\Str::limit($section->media_url, 60) }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                <td>{{ $section->is_active ? 'Так' : 'Ні' }}</td>

                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end gap-2">
                                        <a href="{{ route('admin.testing.sections.edit', $section) }}"
                                           class="btn btn-sm btn-primary">
                                            Редагувати
                                        </a>

                                        <a href="{{ route('admin.testing.tests.questions.index', $test) }}"
                                           class="btn btn-sm btn-outline-secondary">
                                            Питання
                                        </a>

                                        <form action="{{ route('admin.testing.sections.destroy', $section) }}"
                                              method="POST"
                                              onsubmit="return confirm('Видалити секцію?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Видалити
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Секцій поки немає
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                    {{ $sections->links() }}
                </div>
            </div>

        </div>
    </div>
@endsection
