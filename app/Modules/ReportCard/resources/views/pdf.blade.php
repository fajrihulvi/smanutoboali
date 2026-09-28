@php
    /** @var \App\Modules\ReportCard\Models\ReportCard $card */
    $attendance = $card->attendance ?? ['sick' => 0, 'permit' => 0, 'absent' => 0];
    $number = 0;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Raport {{ $card->student->name }}</title>
    <style>
        @page { margin: 28px 36px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #0f172a; }
        h1 { font-size: 15px; margin: 0; letter-spacing: .5px; }
        h2 { font-size: 11.5px; margin: 16px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: middle; }
        .kop img { height: 58px; }
        .kop .school { font-size: 14px; font-weight: bold; }
        .muted { color: #475569; }
        .rule { border-bottom: 2px solid #1e3a8a; margin: 8px 0 12px; }
        .identity td { padding: 2px 0; }
        .grid th, .grid td { border: 1px solid #94a3b8; padding: 5px 6px; vertical-align: top; }
        .grid th { background: #e2e8f0; font-weight: bold; text-align: center; }
        .grid .group td { background: #f1f5f9; font-weight: bold; }
        .center { text-align: center; }
        .note { border: 1px solid #94a3b8; padding: 8px; min-height: 36px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; padding-top: 18px; }
        .signatures img { height: 60px; }
        .signatures .space { height: 60px; }
        .signatures .name { font-weight: bold; text-decoration: underline; }
        .watermark { position: fixed; top: 38%; left: 0; width: 100%; text-align: center; font-size: 64px; color: #e2e8f0; transform: rotate(-30deg); z-index: -1; }
    </style>
</head>
<body>
@if ($isDraft)
    <div class="watermark">DRAF — BELUM TERBIT</div>
@endif

<table class="kop">
    <tr>
        @if ($logo)
            <td style="width: 70px;"><img src="{{ $logo }}" alt="Logo"></td>
        @endif
        <td>
            <div class="school">{{ $school->name }}</div>
            @if ($school->npsn)<div class="muted">NPSN {{ $school->npsn }}</div>@endif
            @if ($school->address)<div class="muted">{{ $school->address }}</div>@endif
        </td>
        <td style="text-align: right;">
            <h1>LAPORAN HASIL BELAJAR</h1>
            <div class="muted">{{ $card->semester->label() }} · TA {{ $card->semester->academicYear->name }}</div>
        </td>
    </tr>
</table>
<div class="rule"></div>

<table class="identity">
    <tr>
        <td style="width: 16%;">Nama</td><td style="width: 44%;">: <strong>{{ $card->student->name }}</strong></td>
        <td style="width: 14%;">Kelas</td><td>: {{ $card->classroom->name }}</td>
    </tr>
    <tr>
        <td>NIS / NISN</td><td>: {{ $card->student->nis }}{{ $card->student->nisn ? ' / '.$card->student->nisn : '' }}</td>
        <td>Semester</td><td>: {{ $card->semester->type->label() }}</td>
    </tr>
</table>

<h2>A. Nilai Akademik</h2>
<table class="grid">
    <thead>
        <tr>
            <th style="width: 4%;">No</th>
            <th style="width: 24%;">Mata Pelajaran</th>
            <th style="width: 9%;">Nilai Akhir</th>
            <th style="width: 8%;">Predikat</th>
            <th>Capaian Kompetensi</th>
        </tr>
    </thead>
    <tbody>
    @foreach ($groups as $label => $details)
        <tr class="group"><td colspan="5">{{ $label }}</td></tr>
        @foreach ($details as $detail)
            <tr>
                <td class="center">{{ ++$number }}</td>
                <td>{{ $detail->subject_name }}</td>
                <td class="center">{{ $detail->final_score ?? '—' }}</td>
                <td class="center">{{ $detail->predicate ?? '—' }}</td>
                <td>{{ $detail->description ?? '—' }}</td>
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>

<h2>B. Ekstrakurikuler</h2>
<table class="grid">
    <thead><tr><th style="width: 4%;">No</th><th style="width: 34%;">Kegiatan</th><th style="width: 14%;">Predikat</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse ($card->extracurriculars as $i => $row)
        <tr><td class="center">{{ $i + 1 }}</td><td>{{ $row->name }}</td><td class="center">{{ $row->predicate ?? '—' }}</td><td>{{ $row->note }}</td></tr>
    @empty
        <tr><td colspan="4" class="center muted">—</td></tr>
    @endforelse
    </tbody>
</table>

<h2>C. Prestasi</h2>
<table class="grid">
    <thead><tr><th style="width: 4%;">No</th><th style="width: 48%;">Prestasi</th><th>Keterangan</th></tr></thead>
    <tbody>
    @forelse ($card->achievements as $i => $row)
        <tr><td class="center">{{ $i + 1 }}</td><td>{{ $row->name }}</td><td>{{ $row->note }}</td></tr>
    @empty
        <tr><td colspan="3" class="center muted">—</td></tr>
    @endforelse
    </tbody>
</table>

<table style="margin-top: 14px;">
    <tr>
        <td style="width: 38%; vertical-align: top; padding-right: 12px;">
            <h2 style="margin-top: 0;">D. Ketidakhadiran</h2>
            <table class="grid">
                <tr><td>Sakit</td><td class="center" style="width: 30%;">{{ $attendance['sick'] }} hari</td></tr>
                <tr><td>Izin</td><td class="center">{{ $attendance['permit'] }} hari</td></tr>
                <tr><td>Tanpa keterangan</td><td class="center">{{ $attendance['absent'] }} hari</td></tr>
            </table>
        </td>
        <td style="vertical-align: top;">
            <h2 style="margin-top: 0;">E. Catatan Wali Kelas</h2>
            <div class="note">{{ $card->homeroom_note ?? '—' }}</div>
        </td>
    </tr>
</table>

<table class="signatures">
    <tr>
        <td>
            Mengetahui,<br>Orang Tua/Wali
            <div class="space"></div>
            <div>(.................................)</div>
        </td>
        <td>
            {{ $date }}<br>Wali Kelas
            @if ($homeroomSignature)<div><img src="{{ $homeroomSignature }}" alt=""></div>@else<div class="space"></div>@endif
            <div class="name">{{ $homeroom?->name ?? '.................................' }}</div>
            @if ($homeroom?->nip)<div>NIP {{ $homeroom->nip }}</div>@endif
        </td>
    </tr>
    <tr>
        <td colspan="2">
            Kepala Sekolah
            @if ($principalSignature)<div><img src="{{ $principalSignature }}" alt=""></div>@else<div class="space"></div>@endif
            <div class="name">{{ $school->principal_name ?? '.................................' }}</div>
            @if ($school->principal_nip)<div>NIP {{ $school->principal_nip }}</div>@endif
        </td>
    </tr>
</table>
</body>
</html>
