@php
    $nav = [
        ['label' => 'Berita', 'url' => url('berita'), 'active' => request()->is('berita*')],
        ['label' => 'Agenda', 'url' => url('agenda'), 'active' => request()->is('agenda*')],
        ['label' => 'Galeri', 'url' => url('galeri'), 'active' => request()->is('galeri*')],
        ['label' => 'Guru', 'url' => url('guru'), 'active' => request()->is('guru')],
        ['label' => 'PPDB', 'url' => url('ppdb'), 'active' => request()->is('ppdb')],
    ];

    if ($contactUrl) {
        $nav[] = ['label' => 'Kontak', 'url' => $contactUrl, 'active' => url()->current() === $contactUrl];
    }

    $profileActive = collect($profileMenu)->contains(fn ($item) => url()->current() === $item['url']);
    $link = 'rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150';
@endphp
<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 sm:px-6">
        <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($school['logo_url'])
                <img src="{{ $school['logo_url'] }}" alt="" class="size-10 shrink-0 rounded-lg object-contain">
            @else
                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand-700 text-white">
                    @include('public.partials.icon', ['name' => 'graduation', 'class' => 'size-5'])
                </span>
            @endif
            <span class="truncate text-base font-bold text-brand-950">{{ $school['name'] }}</span>
        </a>

        <nav aria-label="Menu utama" class="ml-auto hidden items-center gap-1 lg:flex">
            <a href="{{ url('/') }}" @class([$link, 'text-brand-700 bg-brand-50' => request()->is('/'), 'text-slate-600 hover:text-brand-700 hover:bg-slate-50' => ! request()->is('/')])>Beranda</a>
            @if ($profileMenu)
                <details class="group relative">
                    <summary @class([$link, 'flex cursor-pointer list-none items-center gap-1 [&::-webkit-details-marker]:hidden', 'text-brand-700 bg-brand-50' => $profileActive, 'text-slate-600 hover:text-brand-700 hover:bg-slate-50' => ! $profileActive])>
                        Profil
                        @include('public.partials.icon', ['name' => 'chevron-down', 'class' => 'size-4 transition-transform group-open:rotate-180'])
                    </summary>
                    <div class="absolute left-0 mt-2 w-60 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        @foreach ($profileMenu as $item)
                            <a href="{{ $item['url'] }}" class="block rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700">{{ $item['title'] }}</a>
                        @endforeach
                    </div>
                </details>
            @endif
            @foreach ($nav as $item)
                <a href="{{ $item['url'] }}" @class([$link, 'text-brand-700 bg-brand-50' => $item['active'], 'text-slate-600 hover:text-brand-700 hover:bg-slate-50' => ! $item['active']])>{{ $item['label'] }}</a>
            @endforeach
        </nav>

        <a href="{{ url('login') }}" class="ml-auto hidden items-center gap-2 rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 hover:bg-brand-800 lg:ml-2 lg:inline-flex">
            @include('public.partials.icon', ['name' => 'log-in', 'class' => 'size-4'])
            Masuk
        </a>

        {{-- Menu ponsel tanpa JS --}}
        <details class="group ml-auto lg:hidden">
            <summary class="flex size-10 cursor-pointer list-none items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 [&::-webkit-details-marker]:hidden" aria-label="Buka menu">
                <span class="group-open:hidden">@include('public.partials.icon', ['name' => 'menu'])</span>
                <span class="hidden group-open:inline">@include('public.partials.icon', ['name' => 'x'])</span>
            </summary>
            <nav aria-label="Menu ponsel" class="absolute inset-x-0 top-16 max-h-[calc(100vh-4rem)] overflow-y-auto border-b border-slate-200 bg-white px-4 pt-2 pb-5 shadow-lg">
                <a href="{{ url('/') }}" class="block rounded-lg px-3 py-2.5 font-medium text-slate-800 hover:bg-slate-50">Beranda</a>
                @if ($profileMenu)
                    <p class="px-3 pt-3 pb-1 text-xs font-semibold tracking-wide text-slate-400 uppercase">Profil</p>
                    @foreach ($profileMenu as $item)
                        <a href="{{ $item['url'] }}" class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-slate-50">{{ $item['title'] }}</a>
                    @endforeach
                    <div class="my-2 border-t border-slate-100"></div>
                @endif
                @foreach ($nav as $item)
                    <a href="{{ $item['url'] }}" @class(['block rounded-lg px-3 py-2.5 font-medium hover:bg-slate-50', 'text-brand-700' => $item['active'], 'text-slate-800' => ! $item['active']])>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ url('login') }}" class="mt-3 flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 font-semibold text-white hover:bg-brand-800">
                    @include('public.partials.icon', ['name' => 'log-in', 'class' => 'size-4'])
                    Masuk
                </a>
            </nav>
        </details>
    </div>
</header>
