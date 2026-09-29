@extends('public.layout')

@section('content')
    {{-- Banner: slider CSS scroll-snap + skrip kecil untuk tombol & putar otomatis --}}
    @if ($banners->isNotEmpty())
        <section class="relative bg-brand-950" aria-roledescription="carousel" aria-label="Banner">
            <div id="banner-track" class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($banners as $banner)
                    <div class="relative w-full shrink-0 snap-start" aria-roledescription="slide" aria-label="{{ $loop->iteration }} dari {{ $loop->count }}">
                        <img src="{{ $img($banner->image) }}" alt="" @if (! $loop->first) loading="lazy" @endif class="absolute inset-0 size-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-950/90 via-brand-950/60 to-brand-950/10"></div>
                        <div class="relative mx-auto flex min-h-[26rem] max-w-6xl flex-col justify-center px-4 py-16 sm:min-h-[32rem] sm:px-6">
                            <h2 class="max-w-2xl text-3xl font-extrabold tracking-tight text-balance text-white sm:text-5xl">{{ $banner->title }}</h2>
                            @if ($banner->subtitle)
                                <p class="mt-4 max-w-xl text-base text-brand-100 sm:text-lg">{{ $banner->subtitle }}</p>
                            @endif
                            @if ($banner->link_url)
                                <a href="{{ $banner->link_url }}" class="mt-8 inline-flex w-fit items-center gap-2 rounded-lg bg-white px-5 py-3 font-semibold text-brand-800 shadow transition-colors duration-150 hover:bg-brand-50">
                                    {{ $banner->link_label ?: 'Selengkapnya' }}
                                    @include('public.partials.icon', ['name' => 'arrow-right', 'class' => 'size-4'])
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($banners->count() > 1)
                <div class="absolute right-4 bottom-4 flex gap-2 sm:right-6">
                    <button type="button" data-banner="-1" class="flex size-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur hover:bg-white/30" aria-label="Banner sebelumnya">@include('public.partials.icon', ['name' => 'chevron-left'])</button>
                    <button type="button" data-banner="1" class="flex size-10 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur hover:bg-white/30" aria-label="Banner berikutnya">@include('public.partials.icon', ['name' => 'chevron-right'])</button>
                </div>
                <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                    (() => {
                        const track = document.getElementById('banner-track');
                        const go = (dir) => {
                            const w = track.clientWidth;
                            const end = track.scrollLeft + w >= track.scrollWidth - 4;
                            track.scrollTo({ left: dir > 0 && end ? 0 : track.scrollLeft + dir * w });
                        };
                        document.querySelectorAll('[data-banner]').forEach((b) => b.addEventListener('click', () => go(Number(b.dataset.banner))));
                        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                            let paused = false;
                            track.addEventListener('pointerenter', () => (paused = true));
                            track.addEventListener('pointerleave', () => (paused = false));
                            setInterval(() => !paused && !document.hidden && go(1), 7000);
                        }
                    })();
                </script>
            @endif
        </section>
    @else
        <section class="bg-gradient-to-br from-brand-950 via-brand-900 to-brand-700 text-white">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-28">
                <p class="text-sm font-semibold tracking-wide text-brand-accent uppercase">Selamat datang</p>
                <h1 class="mt-3 max-w-3xl text-4xl font-extrabold tracking-tight text-balance sm:text-5xl">{{ $school['name'] }}</h1>
                @if ($settings['tagline'] !== '')
                    <p class="mt-4 max-w-xl text-lg text-brand-100">{{ $settings['tagline'] }}</p>
                @endif
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ url('ppdb') }}" class="rounded-lg bg-white px-5 py-3 font-semibold text-brand-800 hover:bg-brand-50">Info PPDB</a>
                    @if ($profileMenu)
                        <a href="{{ $profileMenu[0]['url'] }}" class="rounded-lg border border-white/30 px-5 py-3 font-semibold text-white hover:bg-white/10">Profil sekolah</a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if ($banners->isNotEmpty())
        <h1 class="sr-only">{{ $school['name'] }}</h1>
    @endif

    {{-- Sambutan --}}
    @if ($greeting)
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="grid items-center gap-10 md:grid-cols-5">
                <div class="md:col-span-2">
                    <div class="mx-auto aspect-[4/5] max-w-60 overflow-hidden rounded-3xl bg-gradient-to-br from-brand-100 to-brand-50 shadow-sm md:max-w-none">
                        @if ($greeting->featuredImage)
                            <img src="{{ $img($greeting->featuredImage) }}" alt="{{ $school['principal_name'] ?? 'Kepala sekolah' }}" loading="lazy" class="size-full object-cover">
                        @else
                            <span class="flex size-full items-center justify-center text-brand-300">@include('public.partials.icon', ['name' => 'user', 'class' => 'size-24'])</span>
                        @endif
                    </div>
                </div>
                <div class="min-w-0 md:col-span-3">
                    <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">{{ $greeting->title }}</p>
                    <h2 class="mt-2 text-2xl font-bold text-brand-950 sm:text-3xl">Selamat datang di {{ $school['name'] }}</h2>
                    <p class="mt-4 text-lg leading-8 text-slate-600">{{ \App\Modules\Cms\Services\PublicSite::describe($greeting->excerpt) ?: \Illuminate\Support\Str::limit(\App\Modules\Cms\Services\PublicSite::describe($greeting->content), 157) }}</p>
                    @if ($school['principal_name'])
                        <p class="mt-5 font-semibold text-slate-900">{{ $school['principal_name'] }}</p>
                        <p class="text-sm text-slate-500">Kepala Sekolah</p>
                    @endif
                    <a href="{{ url($greeting->slug) }}" class="mt-6 inline-flex items-center gap-2 font-semibold text-brand-700 hover:text-brand-800">
                        Baca selengkapnya @include('public.partials.icon', ['name' => 'arrow-right', 'class' => 'size-4'])
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- Berita terbaru --}}
    <section class="bg-slate-50">
        <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="mb-8 flex items-end justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Kabar sekolah</p>
                    <h2 class="mt-1 text-2xl font-bold text-brand-950 sm:text-3xl">Berita terbaru</h2>
                </div>
                <a href="{{ url('berita') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">Semua berita →</a>
            </div>
            @if ($news->isEmpty())
                @include('public.partials.empty', ['title' => 'Belum ada berita', 'icon' => 'file'])
            @else
                <div class="grid gap-6 lg:grid-cols-2">
                    @include('public.partials.post-card', ['post' => $news->first(), 'large' => true])
                    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-1">
                        @foreach ($news->skip(1) as $post)
                            @php $href = url('berita/'.$post->slug); $cover = $img($post->featuredImage); @endphp
                            <article class="flex min-w-0 gap-4 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                <a href="{{ $href }}" class="aspect-square w-24 shrink-0 overflow-hidden rounded-xl bg-brand-50 sm:w-28">
                                    @if ($cover)
                                        <img src="{{ $cover }}" alt="" loading="lazy" class="size-full object-cover">
                                    @endif
                                </a>
                                <div class="min-w-0 py-1">
                                    <p class="text-xs text-slate-500">{{ $post->published_at?->translatedFormat('d F Y') }}</p>
                                    <h3 class="mt-1 line-clamp-2 font-bold leading-snug text-slate-900"><a href="{{ $href }}" class="hover:text-brand-700">{{ $post->title }}</a></h3>
                                    <p class="mt-1 line-clamp-2 text-sm text-slate-600">{{ \App\Modules\Cms\Services\PublicSite::describe($post->excerpt, $post->content) }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Agenda & galeri --}}
    <section class="mx-auto grid max-w-6xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-5">
        <div class="min-w-0 lg:col-span-2">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h2 class="text-2xl font-bold text-brand-950">Agenda mendatang</h2>
                <a href="{{ url('agenda') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">Semua →</a>
            </div>
            @if ($agenda->isEmpty())
                @include('public.partials.empty', ['title' => 'Belum ada agenda', 'icon' => 'calendar'])
            @else
                <ul class="space-y-3">
                    @foreach ($agenda as $event)
                        <li>
                            <a href="{{ url('agenda/'.$event->slug) }}" class="flex gap-4 rounded-2xl border border-slate-200 p-4 transition-colors duration-150 hover:border-brand-300 hover:bg-brand-50/50">
                                <span class="flex w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-700 py-2 text-white">
                                    <span class="text-xl leading-none font-bold">{{ $event->event_starts_at?->format('d') }}</span>
                                    <span class="mt-1 text-xs uppercase">{{ $event->event_starts_at?->translatedFormat('M') }}</span>
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-semibold text-slate-900">{{ $event->title }}</span>
                                    <span class="mt-1 block text-sm text-slate-500">{{ $event->event_starts_at?->translatedFormat('l, H.i') }}@if ($event->event_location) · {{ $event->event_location }}@endif</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="min-w-0 lg:col-span-3">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h2 class="text-2xl font-bold text-brand-950">Galeri kegiatan</h2>
                <a href="{{ url('galeri') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">Semua →</a>
            </div>
            @if ($galleries->isEmpty())
                @include('public.partials.empty', ['title' => 'Belum ada album', 'icon' => 'image'])
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($galleries as $gallery)
                        <a href="{{ url('galeri/'.$gallery->slug) }}" @class(['group relative block overflow-hidden rounded-2xl bg-brand-100', 'sm:col-span-2 aspect-[2/1]' => $loop->first, 'aspect-[4/3]' => ! $loop->first])>
                            @if ($gallery->photos->first())
                                <img src="{{ $img($gallery->photos->first()->file) }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover transition-transform duration-300 group-hover:scale-105">
                            @endif
                            <span class="absolute inset-0 bg-gradient-to-t from-brand-950/80 via-brand-950/10 to-transparent"></span>
                            <span class="absolute inset-x-0 bottom-0 p-4 text-white">
                                <span class="block font-semibold">{{ $gallery->title }}</span>
                                <span class="text-xs text-brand-100">{{ $gallery->photos_count }} foto</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Ajakan PPDB --}}
    <section class="px-4 pb-16 sm:px-6">
        <div class="mx-auto max-w-6xl overflow-hidden rounded-3xl bg-gradient-to-br from-brand-800 via-brand-700 to-brand-600 px-6 py-12 text-white shadow-lg sm:px-12">
            <div class="flex flex-col gap-8 md:flex-row md:items-center md:justify-between">
                <div class="max-w-2xl">
                    @if ($ppdbPeriod)
                        <p class="text-sm font-semibold text-brand-accent">
                            {{ $ppdbPeriod->isOpen() ? 'Pendaftaran dibuka' : 'Segera dibuka' }} ·
                            {{ $ppdbPeriod->opens_at->translatedFormat('d M') }} – {{ $ppdbPeriod->closes_at->translatedFormat('d M Y') }}
                        </p>
                    @endif
                    <h2 class="mt-2 text-2xl font-bold sm:text-3xl">{{ $settings['ppdb_cta_title'] }}</h2>
                    <p class="mt-3 text-brand-100">{{ $settings['ppdb_cta_text'] }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    @if ($ppdbPeriod?->isOpen())
                        <a href="{{ url('ppdb/daftar') }}" class="rounded-lg bg-white px-5 py-3 font-semibold text-brand-800 hover:bg-brand-50">Daftar sekarang</a>
                    @endif
                    <a href="{{ url('ppdb') }}" class="rounded-lg border border-white/40 px-5 py-3 font-semibold text-white hover:bg-white/10">Info PPDB</a>
                </div>
            </div>
        </div>
    </section>
@endsection
