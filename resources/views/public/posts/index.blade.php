@extends('public.layout')

@section('content')
    @include('public.partials.page-hero', [
        'title' => $category ? 'Berita: '.$category->name : 'Berita',
        'subtitle' => 'Kabar kegiatan, prestasi, dan informasi terbaru sekolah.',
        'crumbs' => $category ? [['Berita', url('berita')], [$category->name, null]] : [['Berita', null]],
    ])

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        @if ($categories->isNotEmpty())
            <nav aria-label="Kategori" class="mb-8 flex flex-wrap gap-2">
                <a href="{{ url('berita') }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-brand-700 text-white' => ! $category, 'bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700' => $category])>Semua</a>
                @foreach ($categories as $item)
                    <a href="{{ url('berita?kategori='.$item->slug) }}" @class(['rounded-full px-4 py-1.5 text-sm font-medium', 'bg-brand-700 text-white' => $category?->is($item), 'bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700' => ! $category?->is($item)])>{{ $item->name }}</a>
                @endforeach
            </nav>
        @endif

        @if ($posts->isEmpty())
            @include('public.partials.empty', ['title' => 'Belum ada berita', 'text' => 'Nantikan kabar terbaru dari sekolah kami.'])
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($posts as $post)
                    @include('public.partials.post-card', ['post' => $post])
                @endforeach
            </div>
            @include('public.partials.pagination', ['paginator' => $posts])
        @endif
    </section>
@endsection
