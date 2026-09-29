@extends('public.layout')

@section('content')
    @include('public.partials.page-hero', ['title' => $page->title, 'subtitle' => $page->excerpt, 'crumbs' => [[$page->title, null]]])

    @if ($isContact)
        <section class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-5">
            <div class="min-w-0 space-y-4 lg:col-span-2">
                @if ($page->content)
                    @include('public.partials.prose', ['html' => $page->content])
                @endif
                <ul class="space-y-3">
                    @foreach ([
                        ['map-pin', 'Alamat', $school['address'], null],
                        ['phone', 'Telepon', $school['phone'], 'tel:'.preg_replace('/[^0-9+]/', '', $school['phone'])],
                        ['message', 'WhatsApp', $settings['whatsapp'], $whatsappUrl],
                        ['mail', 'Email', $school['email'], 'mailto:'.$school['email']],
                        ['clock', 'Jam layanan', $settings['opening_hours'], null],
                    ] as [$icon, $label, $value, $href])
                        @if ($value !== '' && $value !== null)
                            <li class="flex gap-4 rounded-2xl border border-slate-200 p-4">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">@include('public.partials.icon', ['name' => $icon])</span>
                                <span class="min-w-0">
                                    <span class="block text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $label }}</span>
                                    @if ($href)
                                        <a href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif class="font-medium break-words text-slate-900 hover:text-brand-700">{{ $value }}</a>
                                    @else
                                        <span class="font-medium break-words text-slate-900">{{ $value }}</span>
                                    @endif
                                </span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
            <div class="min-w-0 lg:col-span-3">
                @if ($settings['map_embed_url'] !== '')
                    <iframe src="{{ $settings['map_embed_url'] }}" title="Peta lokasi {{ $school['name'] }}" class="aspect-[4/3] w-full rounded-2xl border border-slate-200" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                @endif
            </div>
        </section>
    @else
        <article class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            @if ($page->featuredImage)
                <img src="{{ $img($page->featuredImage) }}" alt="" class="mb-8 w-full rounded-2xl object-cover">
            @endif
            @if ($page->content)
                @include('public.partials.prose', ['html' => $page->content])
            @else
                @include('public.partials.empty', ['title' => 'Konten belum tersedia'])
            @endif
        </article>
    @endif
@endsection
