@extends('public.layout')

@section('content')
    @include('public.partials.page-hero', ['title' => 'Galeri', 'subtitle' => 'Dokumentasi foto kegiatan sekolah.', 'crumbs' => [['Galeri', null]]])

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        @if ($galleries->isEmpty())
            @include('public.partials.empty', ['title' => 'Belum ada album', 'icon' => 'image'])
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($galleries as $gallery)
                    <a href="{{ url('galeri/'.$gallery->slug) }}" class="group min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow duration-150 hover:shadow-md">
                        <span class="block aspect-[4/3] overflow-hidden bg-brand-50">
                            @if ($gallery->photos->first())
                                <img src="{{ $thumb($gallery->photos->first()->file) }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-300 group-hover:scale-105">
                            @endif
                        </span>
                        <span class="block p-5">
                            <span class="block text-lg font-bold text-slate-900 group-hover:text-brand-700">{{ $gallery->title }}</span>
                            <span class="mt-1 block text-sm text-slate-500">{{ $gallery->photos_count }} foto{{ $gallery->date ? ' · '.$gallery->date->translatedFormat('d F Y') : '' }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
            @include('public.partials.pagination', ['paginator' => $galleries])
        @endif
    </section>
@endsection
