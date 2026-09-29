<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center">
    <span class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-white text-brand-400 shadow-sm">@include('public.partials.icon', ['name' => $icon ?? 'file', 'class' => 'size-6'])</span>
    <p class="font-semibold text-slate-800">{{ $title }}</p>
    @if (! empty($text))
        <p class="mt-1 text-sm text-slate-500">{{ $text }}</p>
    @endif
</div>
