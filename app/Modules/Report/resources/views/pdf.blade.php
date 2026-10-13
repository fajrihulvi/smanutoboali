@php
    /** @var \App\Modules\Report\Reports\ReportData $data */
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $data->title }}</title>
    <style>
        @page { margin: 26px 30px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: middle; }
        .kop img { height: 48px; }
        .kop .school { font-size: 13px; font-weight: bold; }
        .muted { color: #475569; }
        .rule { border-bottom: 2px solid #196631; margin: 6px 0 10px; }
        h1 { font-size: 13px; margin: 0 0 3px; }
        .meta { margin: 0 0 8px; }
        .grid th, .grid td { border: 1px solid #cbd5e1; padding: 3px 5px; }
        .grid th { background: #e2e8f0; font-weight: bold; text-align: left; }
        .grid tr:nth-child(even) td { background: #f8fafc; }
        .grid tfoot td { background: #effaf1; font-weight: bold; }
        .num { text-align: right; white-space: nowrap; }
        .empty { padding: 12px; text-align: center; color: #64748b; }
        .footer { position: fixed; bottom: -22px; left: 0; right: 0; font-size: 8px; color: #64748b; }
        .page:after { content: counter(page); }
    </style>
</head>
<body>
<div class="footer">
    <table><tr>
        <td>Dicetak {{ $printedAt }} oleh {{ $printedBy }}</td>
        <td style="text-align: right;">Halaman <span class="page"></span></td>
    </tr></table>
</div>

<table class="kop">
    <tr>
        @if ($logo)
            <td style="width: 60px;"><img src="{{ $logo }}" alt="Logo"></td>
        @endif
        <td>
            <div class="school">{{ $school->name }}</div>
            @if ($school->npsn)<div class="muted">NPSN {{ $school->npsn }}</div>@endif
            @if ($school->address)<div class="muted">{{ $school->address }}</div>@endif
        </td>
    </tr>
</table>
<div class="rule"></div>

<h1>{{ $data->title }}</h1>
<div class="meta muted">{{ implode(' · ', $data->meta) }}</div>

<table class="grid">
    <thead>
        <tr>
            @foreach ($data->columns as $i => $column)
                <th @class(['num' => $data->isNumeric($i)])>{{ $column }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($data->rows as $row)
            <tr>
                @foreach ($row as $i => $cell)
                    <td @class(['num' => $data->isNumeric($i)])>{{ $data->display($i, $cell) }}</td>
                @endforeach
            </tr>
        @empty
            <tr><td class="empty" colspan="{{ count($data->columns) }}">Tidak ada data untuk filter ini.</td></tr>
        @endforelse
    </tbody>
    @if ($data->footer)
        <tfoot>
            <tr>
                @foreach ($data->footer as $i => $cell)
                    <td @class(['num' => $data->isNumeric($i)])>{{ $cell === null ? '' : $data->display($i, $cell) }}</td>
                @endforeach
            </tr>
        </tfoot>
    @endif
</table>
</body>
</html>
