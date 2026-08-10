@php
    $isLengthAware = method_exists($paginator, 'lastPage') && method_exists($paginator, 'total');
    $currentPage = $paginator->currentPage();
    $lastPage = $isLengthAware ? max(1, $paginator->lastPage()) : null;
    $pages = collect();

    if ($isLengthAware && $lastPage > 1) {
        $candidatePages = [1, $lastPage];

        if ($currentPage <= 4) {
            for ($page = 1; $page <= min(5, $lastPage); $page++) {
                $candidatePages[] = $page;
            }
        } elseif ($currentPage >= $lastPage - 3) {
            for ($page = max(1, $lastPage - 4); $page <= $lastPage; $page++) {
                $candidatePages[] = $page;
            }
        } else {
            for ($page = $currentPage - 2; $page <= $currentPage + 2; $page++) {
                $candidatePages[] = $page;
            }
        }

        $pages = collect($candidatePages)
            ->filter(fn ($page) => $page >= 1 && $page <= $lastPage)
            ->unique()
            ->sort()
            ->values();
    }
@endphp

@if ($paginator->hasPages())
    <style>
        .ltcms-pagination {
            width: 100%;
            color: #475569;
            font-size: .82rem;
        }

        .ltcms-pagination-mobile {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
        }

        .ltcms-pagination-desktop {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .ltcms-pagination-summary {
            flex: 0 0 auto;
            color: #64748b;
            font-size: .8rem;
            white-space: nowrap;
        }

        .ltcms-pagination-pages {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .3rem;
            min-width: 0;
        }

        .ltcms-page-link,
        .ltcms-page-current,
        .ltcms-page-disabled {
            min-width: 2.15rem;
            height: 2.15rem;
            padding: 0 .55rem;
            border: 1px solid #d7dee8;
            border-radius: .55rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            font-weight: 800;
            line-height: 1;
            text-decoration: none;
            background: #fff;
            color: #334155;
        }

        .ltcms-page-link:hover {
            border-color: #86b89d;
            background: #f0fdf4;
            color: #166534;
        }

        .ltcms-page-current {
            border-color: #166534;
            background: #166534;
            color: #fff;
        }

        .ltcms-page-disabled {
            background: #f8fafc;
            color: #94a3b8;
            cursor: default;
        }

        .ltcms-page-ellipsis {
            min-width: 1.55rem;
            text-align: center;
            color: #94a3b8;
            font-weight: 900;
            user-select: none;
        }

        .ltcms-mobile-link,
        .ltcms-mobile-disabled {
            min-height: 2.35rem;
            padding: 0 .9rem;
            border: 1px solid #d7dee8;
            border-radius: .6rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            color: #334155;
            font-weight: 800;
            text-decoration: none;
        }

        .ltcms-mobile-disabled {
            background: #f8fafc;
            color: #94a3b8;
        }

        .ltcms-mobile-position {
            color: #64748b;
            font-size: .78rem;
            font-weight: 750;
            text-align: center;
        }

        @media (min-width: 768px) {
            .ltcms-pagination-mobile { display: none; }
            .ltcms-pagination-desktop { display: flex; }
        }
    </style>

    <nav class="ltcms-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="ltcms-pagination-mobile">
            @if ($paginator->onFirstPage())
                <span class="ltcms-mobile-disabled" aria-disabled="true">Previous</span>
            @else
                <a class="ltcms-mobile-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
            @endif

            <span class="ltcms-mobile-position">
                Page {{ $currentPage }}@if ($isLengthAware) of {{ $lastPage }}@endif
            </span>

            @if ($paginator->hasMorePages())
                <a class="ltcms-mobile-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
            @else
                <span class="ltcms-mobile-disabled" aria-disabled="true">Next</span>
            @endif
        </div>

        <div class="ltcms-pagination-desktop">
            @if ($isLengthAware)
                <div class="ltcms-pagination-summary">
                    Showing <strong>{{ $paginator->firstItem() }}</strong>–<strong>{{ $paginator->lastItem() }}</strong>
                    of <strong>{{ $paginator->total() }}</strong>
                </div>
            @endif

            <div class="ltcms-pagination-pages">
                @if ($paginator->onFirstPage())
                    <span class="ltcms-page-disabled" aria-disabled="true" aria-label="Previous page">‹</span>
                @else
                    <a class="ltcms-page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">‹</a>
                @endif

                @if ($isLengthAware)
                    @php $previousRenderedPage = null; @endphp
                    @foreach ($pages as $page)
                        @if ($previousRenderedPage !== null && $page - $previousRenderedPage > 1)
                            <span class="ltcms-page-ellipsis" aria-hidden="true">…</span>
                        @endif

                        @if ($page === $currentPage)
                            <span class="ltcms-page-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="ltcms-page-link" href="{{ $paginator->url($page) }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif

                        @php $previousRenderedPage = $page; @endphp
                    @endforeach
                @else
                    <span class="ltcms-page-current">{{ $currentPage }}</span>
                @endif

                @if ($paginator->hasMorePages())
                    <a class="ltcms-page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">›</a>
                @else
                    <span class="ltcms-page-disabled" aria-disabled="true" aria-label="Next page">›</span>
                @endif
            </div>
        </div>
    </nav>
@endif
