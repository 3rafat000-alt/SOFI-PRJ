@if ($paginator->hasPages())
    <div class="pagination">
        @if (! $paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}">السابق</a>
        @endif

        @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if ($page == $paginator->currentPage())
                <span class="active"><span>{{ $page }}</span></span>
            @else
                <a href="{{ $url }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">التالي</a>
        @endif
    </div>
@endif
