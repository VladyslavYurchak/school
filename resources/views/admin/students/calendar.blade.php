<ul>
    @forelse($lessonLogs as $log)
        <li>{{ \Carbon\Carbon::parse($log->date)->setTimezone('Europe/Kyiv')->format('d.m.Y') }} — {{ $log->status }}</li>
    @empty
        <li>Немає занять</li>
    @endforelse
</ul>
