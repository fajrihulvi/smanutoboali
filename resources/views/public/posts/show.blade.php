@extends('public.layout')

@php
    $isAgenda = $post->type->value === 'AGENDA';
    $section = $isAgenda ? ['Agenda', url('agenda')] : ['Berita', url('berita')];
@endphp

@section('content')
    <article>
        <header class="bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 text-white">
            <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
                <nav aria-label="Breadcrumb" class="mb-4 flex flex-wrap items-center gap-1 text-sm text-brand-200">
                    <a href="{{ url('/') }}" class="hover:text-white">Beranda</a>
                    @include('public.partials.icon', ['name' => 'chevron-right', 'class' => 'size-3.5'])
                    <a href="{{ $section[1] }}" class="hover:text-white">{{ $section[0] }}</a>
                </nav>
                @if ($post->category)
                    <a href="{{ url('berita?kategori='.$post->category->slug) }}" class="mb-3 inline-block rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-brand-100 hover:bg-white/20">{{ $post->category->name }}</a>
                @endif
                <h1 class="text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ $post->title }}</h1>
                <p class="mt-4 text-sm text-brand-200">
                    @if ($post->published_at)
                        <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->translatedFormat('l, d F Y') }}</time>
                    @endif
                    @if ($post->author)
                        · {{ $post->author->name }}
                    @endif
                </p>
            </div>
        </header>

        <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
            @if ($isAgenda && $post->event_starts_at)
                <div class="mb-8 grid gap-3 rounded-2xl border border-brand-100 bg-brand-50/60 p-5 sm:grid-cols-2">
                    <p class="flex gap-3">
                        <span class="text-brand-700">@include('public.partials.icon', ['name' => 'calendar'])</span>
                        <span>
                            <span class="block text-xs font-semibold tracking-wide text-slate-500 uppercase">Waktu</span>
                            <span class="font-medium text-slate-900">{{ $post->event_starts_at->translatedFormat('l, d F Y · H.i') }}@if ($post->event_ends_at) – {{ $post->event_ends_at->isSameDay($post->event_starts_at) ? $post->event_ends_at->format('H.i') : $post->event_ends_at->translatedFormat('d F Y · H.i') }}@endif</span>
                        </span>
                    </p>
                    @if ($post->event_location)
                        <p class="flex gap-3">
                            <span class="text-brand-700">@include('public.partials.icon', ['name' => 'map-pin'])</span>
                            <span>
                                <span class="block text-xs font-semibold tracking-wide text-slate-500 uppercase">Lokasi</span>
                                <span class="font-medium text-slate-900">{{ $post->event_location }}</span>
                            </span>
                        </p>
                    @endif
                </div>
            @endif

            @if ($post->featuredImage)
                <img src="{{ $img($post->featuredImage) }}" alt="" class="mb-8 w-full rounded-2xl object-cover">
            @endif

            @if ($post->content)
                @include('public.partials.prose', ['html' => $post->content])
            @elseif ($post->excerpt)
                <p class="text-lg text-slate-700">{{ $post->excerpt }}</p>
            @endif
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="bg-slate-50">
            <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6">
                <h2 class="mb-6 text-2xl font-bold text-brand-950">{{ $isAgenda ? 'Agenda lainnya' : 'Berita lainnya' }}</h2>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($related as $item)
                        @include('public.partials.post-card', ['post' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
