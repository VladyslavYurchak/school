<div>
@if ($paginator->hasPages())
    <ul class="pagination">
        <!-- Попередня сторінка -->
        @if ($paginator->onFirstPage())
            <li class="page-item disabled"><span class="page-link">«</span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">«</a></li>
        @endif

        <!-- Номери сторінок -->
        @foreach ($elements as $element)
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        <!-- Наступна сторінка -->
        @if ($paginator->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">»</a></li>
        @else
            <li class="page-item disabled"><span class="page-link">»</span></li>
        @endif
    </ul>
@endif
</div>
