{{-- Paginasi sederhana: $paginator (LengthAwarePaginator). --}}
@if ($paginator->hasPages())
    <nav aria-label="Halaman" class="mt-10 flex items-center justify-center gap-2 text-sm">
        @if ($paginator->onFirstPage())
            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-slate-300">@include('public.partials.icon', ['name' => 'chevron-left', 'class' => 'size-4']) Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-slate-700 hover:border-brand-300 hover:text-brand-700">@include('public.partials.icon', ['name' => 'chevron-left', 'class' => 'size-4']) Sebelumnya</a>
        @endif
        <span class="px-2 text-slate-500">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-slate-700 hover:border-brand-300 hover:text-brand-700">Berikutnya @include('public.partials.icon', ['name' => 'chevron-right', 'class' => 'size-4'])</a>
        @else
            <span class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-2 text-slate-300">Berikutnya @include('public.partials.icon', ['name' => 'chevron-right', 'class' => 'size-4'])</span>
        @endif
    </nav>
@endif
