@extends('public.layout')

@php
    $input = 'block w-full rounded-lg border bg-white px-3.5 py-2.5 text-slate-900 shadow-xs placeholder:text-slate-400 focus:border-brand-500 focus:ring-2 focus:ring-brand-200 focus:outline-none';
    $border = fn (string $field) => $errors->has($field) ? 'border-rose-400' : 'border-slate-300';
    $label = 'mb-1.5 block text-sm font-semibold text-slate-800';
    $error = 'mt-1.5 text-sm text-rose-600';
@endphp

@section('content')
    @include('public.partials.page-hero', [
        'title' => 'Buku Tamu',
        'subtitle' => 'Selamat datang di '.$school['name'].'. Silakan isi data kunjungan Anda sebelum bertemu pihak sekolah.',
        'crumbs' => [['Buku Tamu', null]],
    ])

    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
        @if (session('guest_book_success'))
            <div class="rounded-3xl border border-emerald-200 bg-emerald-50 p-8 text-center shadow-sm">
                <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                    @include('public.partials.icon', ['name' => 'check', 'class' => 'size-7'])
                </span>
                <h2 class="mt-4 text-2xl font-bold text-brand-950">Terima kasih, {{ session('guest_book_success') }}!</h2>
                <p class="mt-2 text-slate-600">Data kunjungan Anda sudah tercatat. Silakan menunggu, petugas kami akan segera membantu.</p>
                <a href="{{ route('public.guest-book') }}" class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-700 px-5 py-3 font-semibold text-white hover:bg-brand-800">
                    @include('public.partials.icon', ['name' => 'book-open', 'class' => 'size-4'])
                    Isi untuk tamu berikutnya
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('public.guest-book.store') }}" enctype="multipart/form-data" autocomplete="off" novalidate
                  class="space-y-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                @csrf

                @if ($errors->any())
                    <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                        Periksa kembali isian yang ditandai. Jika tadi memilih foto, silakan pilih ulang.
                    </div>
                @endif

                {{-- Honeypot: tidak terlihat oleh pengunjung, hanya diisi bot. --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="full_name" class="{{ $label }}">Nama lengkap <span class="text-rose-600">*</span></label>
                        <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" maxlength="150" required
                               class="{{ $input }} {{ $border('full_name') }}" @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror>
                        @error('full_name')<p id="full_name-error" class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="institution" class="{{ $label }}">Instansi asal <span class="text-rose-600">*</span></label>
                        <input type="text" id="institution" name="institution" value="{{ old('institution') }}" maxlength="150" required placeholder="Mis. Dinas Pendidikan, Wali murid kelas X"
                               class="{{ $input }} {{ $border('institution') }}" @error('institution') aria-invalid="true" aria-describedby="institution-error" @enderror>
                        @error('institution')<p id="institution-error" class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="whatsapp" class="{{ $label }}">Nomor WhatsApp <span class="text-rose-600">*</span></label>
                        <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" inputmode="tel" maxlength="20" required placeholder="08xxxxxxxxxx"
                               class="{{ $input }} {{ $border('whatsapp') }}" @error('whatsapp') aria-invalid="true" aria-describedby="whatsapp-error" @enderror>
                        @error('whatsapp')<p id="whatsapp-error" class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="category" class="{{ $label }}">Kategori tamu <span class="text-rose-600">*</span></label>
                        <select id="category" name="category" required class="{{ $input }} {{ $border('category') }}" @error('category') aria-invalid="true" aria-describedby="category-error" @enderror>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $option)
                                <option value="{{ $option['value'] }}" @selected(old('category') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('category')<p id="category-error" class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="host" class="{{ $label }}">Bertemu dengan <span class="text-rose-600">*</span></label>
                        <select id="host" name="host" required class="{{ $input }} {{ $border('host') }}" @error('host') aria-invalid="true" aria-describedby="host-error" @enderror>
                            <option value="">Pilih yang ingin ditemui</option>
                            @foreach ($hosts as $option)
                                <option value="{{ $option['value'] }}" @selected(old('host') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        @error('host')<p id="host-error" class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="purpose" class="{{ $label }}">Keperluan <span class="text-rose-600">*</span></label>
                        <textarea id="purpose" name="purpose" rows="3" maxlength="1000" required
                                  class="{{ $input }} {{ $border('purpose') }}" @error('purpose') aria-invalid="true" aria-describedby="purpose-error" @enderror>{{ old('purpose') }}</textarea>
                        @error('purpose')<p id="purpose-error" class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <span class="{{ $label }}">Foto tamu <span class="font-normal text-slate-500">(opsional)</span></span>
                        <div class="flex flex-col gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 sm:flex-row sm:items-center">
                            <div id="photo-preview" class="flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white text-slate-300 ring-1 ring-slate-200">
                                @include('public.partials.icon', ['name' => 'user', 'class' => 'size-12'])
                            </div>
                            <div class="space-y-2">
                                <div class="flex flex-wrap gap-2">
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800 focus-within:ring-2 focus-within:ring-brand-300">
                                        @include('public.partials.icon', ['name' => 'camera', 'class' => 'size-4'])
                                        Ambil foto
                                        <input type="file" id="photo-camera" accept="image/*" capture="user" class="sr-only" data-photo-source>
                                    </label>
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700 focus-within:ring-2 focus-within:ring-brand-300">
                                        @include('public.partials.icon', ['name' => 'upload', 'class' => 'size-4'])
                                        Pilih file
                                        <input type="file" id="photo-file" accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-source>
                                    </label>
                                    <button type="button" id="photo-clear" class="hidden rounded-lg px-3 py-2.5 text-sm font-semibold text-rose-600 hover:bg-rose-50">Hapus foto</button>
                                </div>
                                <p class="text-xs text-slate-500">JPG, PNG, atau WebP. "Ambil foto" membuka kamera di HP/tablet.</p>
                            </div>
                        </div>
                        <input type="file" name="photo" id="photo" class="hidden" tabindex="-1">
                        @error('photo')<p class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="{{ $label }}">Catatan <span class="font-normal text-slate-500">(opsional)</span></label>
                        <textarea id="notes" name="notes" rows="2" maxlength="1000"
                                  class="{{ $input }} {{ $border('notes') }}" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ old('notes') }}</textarea>
                        @error('notes')<p id="notes-error" class="{{ $error }}">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">Data hanya dipakai pihak sekolah untuk keperluan kunjungan.</p>
                    <button type="submit" id="guest-submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-700 px-6 py-3 font-semibold text-white hover:bg-brand-800 disabled:opacity-60">
                        @include('public.partials.icon', ['name' => 'check', 'class' => 'size-4'])
                        Kirim
                    </button>
                </div>
            </form>

            <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
                (() => {
                    const MAX = 1280;
                    const target = document.getElementById('photo');
                    const preview = document.getElementById('photo-preview');
                    const clear = document.getElementById('photo-clear');
                    const placeholder = preview.innerHTML;
                    const sources = document.querySelectorAll('[data-photo-source]');

                    const show = (file) => {
                        preview.innerHTML = '';
                        const img = document.createElement('img');
                        img.alt = 'Pratinjau foto tamu';
                        img.className = 'size-full object-cover';
                        img.src = URL.createObjectURL(file);
                        preview.appendChild(img);
                        clear.classList.remove('hidden');
                    };

                    const assign = (file) => {
                        const list = new DataTransfer();
                        list.items.add(file);
                        target.files = list.files;
                        show(file);
                    };

                    // Foto kamera HP bisa beberapa MB: kecilkan dulu (maks 1280 px, JPEG) agar unggah cepat.
                    const shrink = (file) => new Promise((resolve) => {
                        const img = new Image();
                        img.onload = () => {
                            const scale = Math.min(1, MAX / Math.max(img.width, img.height));
                            const canvas = document.createElement('canvas');
                            canvas.width = Math.round(img.width * scale);
                            canvas.height = Math.round(img.height * scale);
                            canvas.getContext('2d').drawImage(img, 0, 0, canvas.width, canvas.height);
                            canvas.toBlob((blob) => resolve(blob ? new File([blob], 'foto-tamu.jpg', { type: 'image/jpeg' }) : file), 'image/jpeg', 0.85);
                            URL.revokeObjectURL(img.src);
                        };
                        img.onerror = () => resolve(file);
                        img.src = URL.createObjectURL(file);
                    });

                    // Peramban lama tanpa DataTransfer: kirim file asli dari tombol "Pilih file".
                    const canAssign = (() => { try { return !!new DataTransfer(); } catch { return false; } })();
                    const fallback = document.getElementById('photo-file');
                    if (!canAssign) {
                        target.remove();
                        fallback.setAttribute('name', 'photo');
                        document.getElementById('photo-camera').closest('label').remove();
                    }

                    sources.forEach((input) => input.addEventListener('change', async () => {
                        const file = input.files && input.files[0];
                        if (!file) return;
                        if (!canAssign) {
                            show(file);
                            return;
                        }
                        input.value = '';
                        assign(await shrink(file));
                    }));

                    clear.addEventListener('click', () => {
                        (canAssign ? target : fallback).value = '';
                        preview.innerHTML = placeholder;
                        clear.classList.add('hidden');
                    });

                    document.querySelector('form[action$="buku-tamu"]').addEventListener('submit', () => {
                        document.getElementById('guest-submit').disabled = true;
                    });
                })();
            </script>
        @endif
    </section>
@endsection
