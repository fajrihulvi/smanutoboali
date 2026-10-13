{{--
    Layout halaman error aplikasi. Sengaja memakai CSS inline (tanpa Vite)
    agar tetap tampil walau public/build belum ada atau aplikasi gagal boot.
    Nilai warna = token design system di resources/css/app.css.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0a2614">
    <title>@yield('code') · @yield('title') — {{ config('app.name', 'SMA NU Toboali') }}</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <style>
        :root {
            --brand-50: #effaf1; --brand-100: #d8f3dd; --brand-500: #2a9d4b; --brand-600: #1d803a;
            --brand-700: #196631; --brand-900: #143f23; --brand-950: #0a2614; --accent: #a3e635;
            --slate-50: #f8fafc; --slate-200: #e2e8f0; --slate-500: #64748b; --slate-900: #0f172a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; flex-direction: column;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--slate-50); color: var(--slate-900); -webkit-font-smoothing: antialiased;
        }
        header {
            background: linear-gradient(90deg, var(--brand-950), var(--brand-900) 60%, var(--brand-700));
            padding: 14px 20px; display: flex; align-items: center; gap: 10px; color: #fff;
        }
        .logo { width: 36px; height: 36px; object-fit: contain; }
        .brand { font-weight: 600; font-size: 16px; letter-spacing: -0.01em; }
        main { flex: 1; display: grid; place-items: center; padding: 40px 16px; }
        .card {
            width: 100%; max-width: 460px; text-align: center; background: #fff;
            border: 1px solid var(--slate-200); border-radius: 16px; padding: 40px 28px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
        }
        .badge {
            width: 88px; height: 88px; margin: 0 auto 20px; border-radius: 999px; display: grid; place-items: center;
            background: var(--brand-50); box-shadow: inset 0 0 0 12px var(--brand-100);
            color: var(--brand-600); font-weight: 700; font-size: 22px; font-variant-numeric: tabular-nums;
        }
        h1 { margin: 0; font-size: 22px; font-weight: 600; letter-spacing: -0.01em; }
        p { margin: 10px 0 0; color: var(--slate-500); font-size: 14px; line-height: 1.6; }
        .actions { margin-top: 28px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        a.btn {
            display: inline-flex; align-items: center; height: 38px; padding: 0 16px; border-radius: 10px;
            font-size: 14px; font-weight: 500; text-decoration: none; transition: background-color .15s, color .15s;
        }
        a.primary { background: var(--brand-600); color: #fff; }
        a.primary:hover { background: var(--brand-700); }
        a.secondary { color: var(--slate-900); border: 1px solid var(--slate-200); background: #fff; }
        a.secondary:hover { background: var(--brand-50); color: var(--brand-700); }
        a:focus-visible { outline: 3px solid rgba(42, 157, 75, .4); outline-offset: 2px; }
        footer { padding: 16px; text-align: center; font-size: 12px; color: var(--slate-500); }
    </style>
</head>
<body>
    <header>
        <img class="logo" src="{{ asset('images/logo.png') }}" alt="" aria-hidden="true">
        <span class="brand">{{ config('app.name', 'SMA NU Toboali') }}</span>
    </header>

    <main>
        <div class="card">
            <div class="badge">@yield('code')</div>
            <h1>@yield('title')</h1>
            <p>@yield('message')</p>
            <div class="actions">
                @hasSection('actions')
                    @yield('actions')
                @else
                    <a class="btn secondary" href="{{ url()->previous() }}">Kembali</a>
                    <a class="btn primary" href="{{ url('/dashboard') }}">Ke dashboard</a>
                @endif
            </div>
        </div>
    </main>

    <footer>Sistem Informasi Sekolah {{ config('app.name', 'SMA NU Toboali') }}</footer>
</body>
</html>
