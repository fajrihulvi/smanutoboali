{{-- Layout website publik (S29). Data bersama dari PublicSite (view composer). --}}
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('public.partials.seo')
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/images/favicon-192.png" type="image/png" sizes="192x192">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/public.css')
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-slate-800 antialiased">
    <a href="#konten" class="sr-only z-50 rounded-md bg-brand-700 px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Lewati ke konten</a>

    @if ($preview ?? false)
        <div class="sticky top-0 z-50 bg-amber-400 px-4 py-2 text-center text-sm font-semibold text-amber-950">
            Pratinjau — halaman ini belum tentu terlihat publik.
        </div>
    @endif

    @include('public.partials.header')

    <main id="konten" class="flex-1">
        @yield('content')
    </main>

    @include('public.partials.footer')
</body>
</html>
