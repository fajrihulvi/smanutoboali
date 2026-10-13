<footer class="bg-brand-950 text-brand-100">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3">
        <div class="space-y-3">
            <p class="text-lg font-bold text-white">{{ $school['name'] }}</p>
            @if ($settings['tagline'] !== '')
                <p class="text-sm text-brand-200">{{ $settings['tagline'] }}</p>
            @endif
            @if ($settings['about'] !== '')
                <p class="text-sm leading-6 text-brand-100/80">{{ $settings['about'] }}</p>
            @endif
        </div>

        <div class="space-y-3 text-sm">
            <p class="font-semibold text-white">Kontak</p>
            <ul class="space-y-2.5">
                @if ($school['address'] !== '')
                    <li class="flex gap-2.5">@include('public.partials.icon', ['name' => 'map-pin', 'class' => 'mt-0.5 size-4 shrink-0 text-brand-accent'])<span>{{ $school['address'] }}</span></li>
                @endif
                @if ($school['phone'] !== '')
                    <li class="flex gap-2.5">@include('public.partials.icon', ['name' => 'phone', 'class' => 'mt-0.5 size-4 shrink-0 text-brand-accent'])<a href="tel:{{ preg_replace('/[^0-9+]/', '', $school['phone']) }}" class="hover:text-white">{{ $school['phone'] }}</a></li>
                @endif
                @if ($whatsappUrl)
                    <li class="flex gap-2.5">@include('public.partials.icon', ['name' => 'message', 'class' => 'mt-0.5 size-4 shrink-0 text-brand-accent'])<a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="hover:text-white">WhatsApp {{ $settings['whatsapp'] }}</a></li>
                @endif
                @if ($school['email'] !== '')
                    <li class="flex gap-2.5">@include('public.partials.icon', ['name' => 'mail', 'class' => 'mt-0.5 size-4 shrink-0 text-brand-accent'])<a href="mailto:{{ $school['email'] }}" class="break-all hover:text-white">{{ $school['email'] }}</a></li>
                @endif
                @if ($settings['opening_hours'] !== '')
                    <li class="flex gap-2.5">@include('public.partials.icon', ['name' => 'clock', 'class' => 'mt-0.5 size-4 shrink-0 text-brand-accent'])<span>{{ $settings['opening_hours'] }}</span></li>
                @endif
            </ul>
        </div>

        <div class="space-y-3 text-sm">
            <p class="font-semibold text-white">Tautan</p>
            <ul class="grid grid-cols-2 gap-2">
                <li><a href="{{ url('berita') }}" class="hover:text-white">Berita</a></li>
                <li><a href="{{ url('agenda') }}" class="hover:text-white">Agenda</a></li>
                <li><a href="{{ url('galeri') }}" class="hover:text-white">Galeri</a></li>
                <li><a href="{{ url('guru') }}" class="hover:text-white">Guru</a></li>
                <li><a href="{{ url('ppdb') }}" class="hover:text-white">PPDB</a></li>
                <li><a href="{{ url('ppdb/hasil') }}" class="hover:text-white">Cek hasil PPDB</a></li>
                <li><a href="{{ url('login') }}" class="hover:text-white">Masuk portal</a></li>
            </ul>
            @if ($socials)
                <div class="flex flex-wrap gap-2 pt-2">
                    @foreach ($socials as $label => $href)
                        <a href="{{ $href }}" target="_blank" rel="noopener" class="rounded-full border border-brand-800 px-3 py-1 text-xs hover:border-brand-accent hover:text-white">{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="border-t border-brand-900">
        <div class="mx-auto flex max-w-6xl flex-col gap-1 px-4 py-5 text-xs text-brand-200/70 sm:flex-row sm:justify-between sm:px-6">
            <p>&copy; {{ now()->year }} {{ $school['name'] }}. {{ $settings['footer_text'] }}</p>
            <p>Sistem Informasi Sekolah</p>
        </div>
    </div>
</footer>
