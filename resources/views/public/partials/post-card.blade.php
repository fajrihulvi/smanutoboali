{{-- Kartu berita/agenda: $post, $large (opsional). --}}
@php
    $href = url(($post->type->value === 'AGENDA' ? 'agenda' : 'berita').'/'.$post->slug);
    $large = $large ?? false;
    $cover = $img($post->featuredImage);
@endphp
<article class="group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-shadow duration-150 hover:shadow-md">
    <a href="{{ $href }}" class="block {{ $large ? 'aspect-[16/10]' : 'aspect-video' }} overflow-hidden bg-brand-50">
        @if ($cover)
            <img src="{{ $cover }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-300 group-hover:scale-105">
        @else
            <span class="flex size-full items-center justify-center text-brand-200">@include('public.partials.icon', ['name' => 'image', 'class' => 'size-10'])</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col gap-2 p-5">
        <p class="flex flex-wrap items-center gap-x-2 text-xs font-medium text-slate-500">
            @if ($post->category)
                <span class="rounded-full bg-brand-50 px-2 py-0.5 text-brand-700">{{ $post->category->name }}</span>
            @endif
            @if ($post->type->value === 'AGENDA' && $post->event_starts_at)
                <span>{{ $post->event_starts_at->translatedFormat('d F Y, H.i') }}</span>
            @elseif ($post->published_at)
                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->translatedFormat('d F Y') }}</time>
            @endif
        </p>
        <h3 class="{{ $large ? 'text-xl sm:text-2xl' : 'text-lg' }} leading-snug font-bold text-slate-900">
            <a href="{{ $href }}" class="hover:text-brand-700">{{ $post->title }}</a>
        </h3>
        <p class="line-clamp-3 text-sm leading-6 text-slate-600">{{ \App\Modules\Cms\Services\PublicSite::describe($post->excerpt, $post->content) }}</p>
    </div>
</article>
