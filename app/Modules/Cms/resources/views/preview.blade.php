{{-- Pratinjau konten CMS (S28). Isi HTML sudah disanitasi saat disimpan. --}}
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Pratinjau: {{ $title }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; color: #0f172a; background: #f8fafc; line-height: 1.7; }
        .bar { position: sticky; top: 0; background: #1e3a8a; color: #fff; padding: 10px 16px; font-size: 14px; display: flex; gap: 12px; flex-wrap: wrap; align-items: center; justify-content: space-between; }
        .bar span { opacity: .85; }
        .wrap { max-width: 760px; margin: 0 auto; padding: 32px 16px 64px; }
        .kind { color: #1d4ed8; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: .05em; }
        h1 { font-size: 30px; line-height: 1.25; margin: 6px 0 8px; }
        .meta { color: #64748b; font-size: 14px; display: flex; flex-wrap: wrap; gap: 4px 16px; }
        .hero { width: 100%; border-radius: 12px; margin: 24px 0; }
        .content img { max-width: 100%; height: auto; border-radius: 8px; }
        .content a { color: #1d4ed8; }
        .content blockquote { margin: 16px 0; padding: 4px 16px; border-left: 4px solid #bfdbfe; color: #334155; }
        .content h2, .content h3, .content h4 { line-height: 1.3; margin-top: 28px; }
        .content pre { background: #0f172a; color: #e2e8f0; padding: 12px; border-radius: 8px; overflow-x: auto; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-top: 24px; }
        .grid figure { margin: 0; }
        .grid img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 8px; display: block; }
        .grid figcaption { font-size: 13px; color: #475569; margin-top: 4px; }
        .empty { color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>
    <div class="bar">
        <strong>Pratinjau</strong>
        <span>Status: {{ $status }} · Halaman ini tidak terlihat publik</span>
    </div>
    <main class="wrap">
        <div class="kind">{{ $kind }}</div>
        <h1>{{ $title }}</h1>
        @if ($meta)
            <div class="meta">
                @foreach ($meta as $item)
                    <span>{{ $item }}</span>
                @endforeach
            </div>
        @endif

        @if ($image)
            <img class="hero" src="{{ app(\App\Modules\Core\Services\FileUrlGenerator::class)->url($image) }}" alt="">
        @endif

        <article class="content">
            @if ($content)
                {!! $content !!}
            @elseif (empty($photos))
                <p class="empty">Belum ada isi.</p>
            @endif
        </article>

        @if (! empty($photos) && count($photos))
            <div class="grid">
                @foreach ($photos as $photo)
                    <figure>
                        <img src="{{ app(\App\Modules\Core\Services\FileUrlGenerator::class)->url($photo->file) }}" alt="{{ $photo->caption }}" loading="lazy">
                        @if ($photo->caption)
                            <figcaption>{{ $photo->caption }}</figcaption>
                        @endif
                    </figure>
                @endforeach
            </div>
        @endif
    </main>
</body>
</html>
