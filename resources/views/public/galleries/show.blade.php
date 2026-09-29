@extends('public.layout')

@section('content')
    @include('public.partials.page-hero', [
        'title' => $gallery->title,
        'subtitle' => trim(($gallery->date ? $gallery->date->translatedFormat('d F Y').' · ' : '').$gallery->photos->count().' foto'),
        'crumbs' => [['Galeri', url('galeri')], [$gallery->title, null]],
    ])

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        @if ($gallery->description)
            <p class="mb-8 max-w-3xl text-lg leading-8 text-slate-600">{{ $gallery->description }}</p>
        @endif
        @if ($gallery->photos->isEmpty())
            @include('public.partials.empty', ['title' => 'Belum ada foto', 'icon' => 'image'])
        @else
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                @foreach ($gallery->photos as $photo)
                    <figure class="min-w-0">
                        <a href="{{ $img($photo->file) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl bg-brand-50">
                            <img src="{{ $thumb($photo->file) }}" alt="{{ $photo->caption ?? $gallery->title }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition-transform duration-300 hover:scale-105">
                        </a>
                        @if ($photo->caption)
                            <figcaption class="mt-2 text-sm text-slate-600">{{ $photo->caption }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @endif
    </section>
@endsection
