@if ($paginator->hasPages())
    <div class="portal-pagination-layout">
        <p class="portal-pagination-summary">
            Showing <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> results
        </p>

        <nav class="portal-pagination" role="navigation" aria-label="Pagination">
            <ul>
                <li>
                    @if ($paginator->onFirstPage())
                        <span class="is-disabled" aria-disabled="true" aria-label="Previous page"><span aria-hidden="true">&#8249;</span></span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page"><span aria-hidden="true">&#8249;</span></a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li><span class="is-disabled" aria-disabled="true">{{ $element }}</span></li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span class="is-current" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page"><span aria-hidden="true">&#8250;</span></a>
                    @else
                        <span class="is-disabled" aria-disabled="true" aria-label="Next page"><span aria-hidden="true">&#8250;</span></span>
                    @endif
                </li>
            </ul>
        </nav>
    </div>
@endif
