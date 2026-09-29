@extends('public.layout')

@section('content')
    @include('public.partials.page-hero', ['title' => $settings['ppdb_cta_title'], 'subtitle' => $settings['ppdb_cta_text'], 'crumbs' => [['PPDB', null]]])

    <section class="mx-auto max-w-5xl space-y-10 px-4 py-12 sm:px-6">
        @forelse ($periods as $period)
            @php $open = $period->isOpen(); @endphp
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50 p-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <span @class(['inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-700' => $open, 'bg-amber-100 text-amber-700' => ! $open])>
                            <span @class(['size-1.5 rounded-full', 'bg-emerald-500' => $open, 'bg-amber-500' => ! $open])></span>
                            {{ $open ? 'Pendaftaran dibuka' : 'Segera dibuka' }}
                        </span>
                        <h2 class="mt-2 text-2xl font-bold text-brand-950">{{ $period->name }}</h2>
                        <p class="text-sm text-slate-500">Tahun ajaran {{ $period->academicYear->name }} · Kelas {{ $period->grade }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if ($open)
                            <a href="{{ url('ppdb/daftar') }}" class="rounded-lg bg-brand-700 px-5 py-2.5 font-semibold text-white hover:bg-brand-800">Daftar sekarang</a>
                        @endif
                        <a href="{{ url('ppdb/masuk') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700">Masuk akun pendaftar</a>
                    </div>
                </div>
                <div class="grid gap-6 p-6 md:grid-cols-3">
                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="text-slate-500">Pendaftaran</dt>
                            <dd class="font-semibold text-slate-900">{{ $period->opens_at->translatedFormat('d F Y') }} – {{ $period->closes_at->translatedFormat('d F Y') }}</dd>
                        </div>
                        @if ($period->announces_at)
                            <div>
                                <dt class="text-slate-500">Pengumuman hasil</dt>
                                <dd class="font-semibold text-slate-900">{{ $period->announces_at->translatedFormat('d F Y') }}</dd>
                            </div>
                        @endif
                        @if ($period->description)
                            <div>
                                <dt class="text-slate-500">Keterangan</dt>
                                <dd class="whitespace-pre-line text-slate-700">{{ $period->description }}</dd>
                            </div>
                        @endif
                    </dl>
                    <div class="min-w-0 space-y-4 md:col-span-2">
                        @foreach ($period->tracks as $track)
                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <h3 class="font-bold text-slate-900">Jalur {{ $track->name }}</h3>
                                    <span class="text-sm font-medium text-brand-700">Kuota {{ $track->quota }} siswa</span>
                                </div>
                                @if ($track->description)
                                    <p class="mt-1 text-sm text-slate-600">{{ $track->description }}</p>
                                @endif
                                @if ($track->requirements->isNotEmpty())
                                    <p class="mt-3 text-xs font-semibold tracking-wide text-slate-500 uppercase">Dokumen</p>
                                    <ul class="mt-1 grid gap-1.5 text-sm sm:grid-cols-2">
                                        @foreach ($track->requirements as $requirement)
                                            <li class="flex gap-2 text-slate-700">
                                                <span class="mt-0.5 text-emerald-600">@include('public.partials.icon', ['name' => 'check', 'class' => 'size-4'])</span>
                                                <span>{{ $requirement->name }}@unless ($requirement->is_required) <span class="text-slate-400">(opsional)</span>@endunless</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            @include('public.partials.empty', ['title' => 'Belum ada periode pendaftaran', 'text' => 'Informasi PPDB akan diumumkan di halaman ini.', 'icon' => 'calendar'])
        @endforelse

        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['1', 'Buat akun', 'Daftar dengan email aktif dan verifikasi email Anda.'],
                ['2', 'Isi formulir & unggah dokumen', 'Data tersimpan otomatis sebagai draf sampai Anda kirim.'],
                ['3', 'Pantau hasil', 'Lihat status pendaftaran dan hasil seleksi secara online.'],
            ] as [$step, $title, $text])
                <div class="rounded-2xl bg-brand-50 p-5">
                    <span class="flex size-8 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white">{{ $step }}</span>
                    <p class="mt-3 font-semibold text-brand-950">{{ $title }}</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $text }}</p>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col items-start gap-4 rounded-3xl bg-brand-950 p-6 text-white sm:flex-row sm:items-center sm:justify-between sm:p-8">
            <div>
                <h2 class="text-xl font-bold">Cek hasil seleksi</h2>
                <p class="mt-1 text-sm text-brand-200">Masukkan nomor pendaftaran dan tanggal lahir calon siswa.{{ $hasPublishedResults ? '' : ' Hasil akan tersedia setelah diumumkan.' }}</p>
            </div>
            <a href="{{ url('ppdb/hasil') }}" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-white px-5 py-2.5 font-semibold text-brand-800 hover:bg-brand-50">
                @include('public.partials.icon', ['name' => 'search', 'class' => 'size-4']) Cek hasil
            </a>
        </div>
    </section>
@endsection
