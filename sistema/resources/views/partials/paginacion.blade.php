@if ($paginator->hasPages())
    <nav class="muted" style="display:flex;gap:14px;align-items:center">
        @if (! $paginator->onFirstPage())<a href="{{ $paginator->previousPageUrl() }}">« Anterior</a>@endif
        <span>Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">Siguiente »</a>@endif
    </nav>
@endif
