@extends('public.layout')

@section('content')
    @include('public.partials.page-hero', ['title' => 'Agenda', 'subtitle' => 'Jadwal kegiatan sekolah untuk siswa dan orang tua.', 'crumbs' => [['Agenda', null]]])

    <section class="mx-auto max-w-4xl px-4 py-12 sm:px-6">
        <h2 class="mb-5 text-xl font-bold text-brand-950">Akan datang</h2>
        @if ($upcoming->isEmpty())
            @include('public.partials.empty', ['title' => 'Belum ada agenda mendatang', 'icon' => 'calendar'])
        @else
            <ul class="space-y-4">
                @foreach ($upcoming as $event)
                    <li>
                        <a href="{{ url('agenda/'.$event->slug) }}" class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300 sm:gap-6 sm:p-5">
                            <span class="flex w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-brand-700 py-3 text-white">
                                <span class="text-2xl leading-none font-bold">{{ $event->event_starts_at?->format('d') }}</span>
                                <span class="mt-1 text-xs uppercase">{{ $event->event_starts_at?->translatedFormat('M Y') }}</span>
                            </span>
                            <span class="min-w-0 space-y-1">
                                <span class="block text-lg font-semibold text-slate-900">{{ $event->title }}</span>
                                <span class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-500">
                                    <span class="inline-flex items-center gap-1.5">@include('public.partials.icon', ['name' => 'clock', 'class' => 'size-4'])
                                        {{ $event->event_starts_at?->translatedFormat('l, H.i') }}@if ($event->event_ends_at) – {{ $event->event_ends_at->isSameDay($event->event_starts_at) ? $event->event_ends_at->format('H.i') : $event->event_ends_at->translatedFormat('d M, H.i') }}@endif
                                    </span>
                                    @if ($event->event_location)
                                        <span class="inline-flex items-center gap-1.5">@include('public.partials.icon', ['name' => 'map-pin', 'class' => 'size-4']) {{ $event->event_location }}</span>
                                    @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($past->isNotEmpty())
            <h2 class="mt-14 mb-5 text-xl font-bold text-brand-950">Sudah berlangsung</h2>
            <ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200">
                @foreach ($past as $event)
                    <li>
                        <a href="{{ url('agenda/'.$event->slug) }}" class="flex flex-col gap-1 px-5 py-4 hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between">
                            <span class="font-medium text-slate-800">{{ $event->title }}</span>
                            <span class="shrink-0 text-sm text-slate-500">{{ $event->event_starts_at?->translatedFormat('d F Y') }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            @include('public.partials.pagination', ['paginator' => $past])
        @endif
    </section>
@endsection
