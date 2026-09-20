@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="Navigasi halaman">
        @if ($paginator->onFirstPage())
            <span class="pagination-control disabled" aria-disabled="true">Sebelumnya</span>
        @else
            <a class="pagination-control" href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif

        <div class="pagination-pages" aria-label="Halaman">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-page active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-page" href="{{ $url }}" aria-label="Buka halaman {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="pagination-control" href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @else
            <span class="pagination-control disabled" aria-disabled="true">Berikutnya</span>
        @endif
    </nav>
@endif
