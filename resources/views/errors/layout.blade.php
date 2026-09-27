{{--
    Layout halaman error Skoolia. Sengaja memakai CSS inline (tanpa Vite)
    agar tetap tampil walau public/build belum ada atau aplikasi gagal boot.
    Nilai warna = token design system di resources/css/app.css.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#0a1a3f">
    <title>@yield('code') · @yield('title') — {{ config('app.name', 'Skoolia') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root {
            --brand-50: #eff6ff; --brand-100: #dbeafe; --brand-500: #3b82f6; --brand-600: #2563eb;
            --brand-700: #1d4ed8; --brand-900: #0f2a5f; --brand-950: #0a1a3f; --accent: #38bdf8;
            --slate-50: #f8fafc; --slate-200: #e2e8f0; --slate-500: #64748b; --slate-900: #0f172a;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; flex-direction: column;
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            background: var(--slate-50); color: var(--slate-900); -webkit-font-smoothing: antialiased;
        }
        header {
            background: linear-gradient(90deg, var(--brand-950), var(--brand-900));
            padding: 14px 20px; display: flex; align-items: center; gap: 10px; color: #fff;
        }
        .logo {
            width: 34px; height: 34px; border-radius: 10px; display: grid; place-items: center;
            background: linear-gradient(135deg, var(--brand-500), var(--accent));
        }
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
        a:focus-visible { outline: 3px solid rgba(59, 130, 246, .4); outline-offset: 2px; }
        footer { padding: 16px; text-align: center; font-size: 12px; color: var(--slate-500); }
    </style>
</head>
<body>
    <header>
        <span class="logo" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/></svg>
        </span>
        <span class="brand">Skoolia</span>
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
                    <a class="btn secondary" href="javascript:history.back()">Kembali</a>
                    <a class="btn primary" href="{{ url('/dashboard') }}">Ke dashboard</a>
                @endif
            </div>
        </div>
    </main>

    <footer>Sistem Informasi Sekolah Skoolia</footer>
</body>
</html>
