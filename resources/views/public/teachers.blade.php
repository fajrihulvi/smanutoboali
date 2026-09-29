@extends('public.layout')

@section('content')
    @include('public.partials.page-hero', ['title' => 'Guru & Tenaga Kependidikan', 'subtitle' => 'Para pendidik yang mendampingi putra-putri Anda setiap hari.', 'crumbs' => [['Guru', null]]])

    <section class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
        @if ($teachers->isEmpty())
            @include('public.partials.empty', ['title' => 'Data guru belum ditampilkan', 'icon' => 'user'])
        @else
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 sm:gap-6 lg:grid-cols-4">
                @foreach ($teachers as $teacher)
                    @php
                        $initials = collect(explode(' ', preg_replace('/,.*$/', '', $teacher->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    @endphp
                    <li class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white text-center shadow-sm">
                        <div class="aspect-[4/5] bg-gradient-to-br from-brand-100 to-brand-50">
                            @if ($teacher->photo)
                                <img src="{{ $thumb($teacher->photo) }}" alt="{{ $teacher->name }}" loading="lazy" class="size-full object-cover">
                            @else
                                <span class="flex size-full items-center justify-center text-4xl font-bold text-brand-400">{{ $initials }}</span>
                            @endif
                        </div>
                        <div class="p-4">
                            <p class="font-semibold break-words text-slate-900">{{ $teacher->name }}</p>
                            @if ($teacher->website_title)
                                <p class="mt-0.5 text-sm text-brand-700">{{ $teacher->website_title }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
