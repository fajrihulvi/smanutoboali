{{-- Pita judul halaman: $title, $subtitle (opsional), $crumbs [[label, url]]. --}}
<section class="bg-gradient-to-br from-brand-950 via-brand-900 to-brand-800 text-white">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">
        <nav aria-label="Breadcrumb" class="mb-3 flex flex-wrap items-center gap-1 text-sm text-brand-200">
            <a href="{{ url('/') }}" class="hover:text-white">Beranda</a>
            @foreach ($crumbs ?? [] as [$label, $href])
                @include('public.partials.icon', ['name' => 'chevron-right', 'class' => 'size-3.5'])
                @if ($href)
                    <a href="{{ $href }}" class="hover:text-white">{{ $label }}</a>
                @else
                    <span class="text-white/90">{{ $label }}</span>
                @endif
            @endforeach
        </nav>
        <h1 class="max-w-3xl text-3xl font-bold tracking-tight text-balance sm:text-4xl">{{ $title }}</h1>
        @if (! empty($subtitle))
            <p class="mt-3 max-w-2xl text-brand-100/90">{{ $subtitle }}</p>
        @endif
    </div>
</section>
