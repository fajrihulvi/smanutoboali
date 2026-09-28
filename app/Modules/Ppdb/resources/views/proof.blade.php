<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pendaftaran {{ $applicant->registration_number }}</title>
    <style>
        body { font-family: 'dejavu sans', sans-serif; font-size: 11px; color: #0f172a; }
        .head { border-bottom: 2px solid #1e40af; padding-bottom: 8px; margin-bottom: 14px; }
        .school { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .muted { color: #64748b; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .number { font-size: 20px; font-weight: bold; color: #1e3a8a; letter-spacing: 1px; }
        h2 { font-size: 12px; margin: 16px 0 6px; color: #1e3a8a; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        td.label { width: 32%; color: #64748b; }
        .box { border: 1px dashed #94a3b8; padding: 10px; margin-top: 18px; }
    </style>
</head>
<body>
    <div class="head">
        <div class="school">{{ $school->name }}</div>
        <div class="muted">{{ $school->address }}</div>
    </div>

    <h1>BUKTI PENDAFTARAN PPDB</h1>
    <div class="muted">{{ $applicant->period->name }} · Tahun ajaran {{ $applicant->period->academicYear->name }} · Kelas {{ $applicant->period->grade }} · Jalur {{ $applicant->track->name }}</div>
    <p>Nomor pendaftaran<br><span class="number">{{ $applicant->registration_number }}</span></p>

    <h2>Data calon siswa</h2>
    <table>
        <tr><td class="label">Nama lengkap</td><td>{{ $applicant->name }}</td></tr>
        <tr><td class="label">NIK / NISN</td><td>{{ $applicant->nik }} / {{ $applicant->nisn ?? '-' }}</td></tr>
        <tr><td class="label">Jenis kelamin</td><td>{{ $applicant->gender?->label() }}</td></tr>
        <tr><td class="label">Tempat, tanggal lahir</td><td>{{ $applicant->birth_place }}, {{ $applicant->birth_date?->locale('id')->translatedFormat('j F Y') }}</td></tr>
        <tr><td class="label">Agama</td><td>{{ $applicant->religion?->label() }}</td></tr>
        <tr><td class="label">Alamat</td><td>{{ $applicant->address }}</td></tr>
        <tr><td class="label">Asal sekolah</td><td>{{ $applicant->previous_school ?? '-' }}</td></tr>
    </table>

    <h2>Orang tua / wali</h2>
    <table>
        @foreach ($applicant->guardians as $guardian)
            <tr><td class="label">{{ $guardian->relationship->label() }}{{ $guardian->is_primary ? ' (kontak utama)' : '' }}</td><td>{{ $guardian->name }} · {{ $guardian->phone }}{{ $guardian->occupation ? ' · '.$guardian->occupation : '' }}</td></tr>
        @endforeach
    </table>

    <h2>Dokumen</h2>
    <table>
        @foreach ($applicant->track->requirements as $requirement)
            <tr><td class="label">{{ $requirement->name }}</td><td>{{ in_array($requirement->id, $uploaded, true) ? 'Terunggah' : ($requirement->is_required ? 'Belum ada' : '-') }}</td></tr>
        @endforeach
    </table>

    <div class="box">
        Status: <strong>{{ $applicant->status->label() }}</strong> · dikirim {{ $applicant->submitted_at?->locale('id')->translatedFormat('j F Y H.i') }}<br>
        @if ($applicant->period->announces_at)
            Pengumuman hasil: {{ $applicant->period->announces_at->locale('id')->translatedFormat('j F Y') }}.<br>
        @endif
        Simpan bukti ini. Pantau status pendaftaran di portal PPDB.
    </div>
</body>
</html>
