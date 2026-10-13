@php
    $rupiah = fn (int $v) => 'Rp'.number_format($v, 0, ',', '.');
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Kwitansi {{ $payment->receipt_number }}</title>
    <style>
        body { font-family: 'dejavu sans', sans-serif; font-size: 10.5px; color: #0f172a; margin: 0; }
        .head { border-bottom: 2px solid #196631; padding-bottom: 6px; margin-bottom: 10px; }
        .school { font-size: 14px; font-weight: bold; color: #17512a; }
        .muted { color: #64748b; }
        h1 { font-size: 16px; margin: 0 0 8px; letter-spacing: 1px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 0; vertical-align: top; }
        .items td { border-bottom: 1px solid #e2e8f0; padding: 4px 0; }
        .right { text-align: right; }
        .total { font-size: 13px; font-weight: bold; color: #17512a; }
        .stamp { border: 2px solid #059669; color: #059669; padding: 4px 10px; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            @if ($logo)
                <td style="width: 54px; vertical-align: middle;"><img src="{{ $logo }}" alt="Logo" style="height: 44px;"></td>
            @endif
            <td style="vertical-align: middle;">
                <div class="school">{{ $school->name }}</div>
                <div class="muted">{{ $school->address }}</div>
            </td>
        </tr>
    </table>

    <table>
        <tr>
            <td style="width: 60%">
                <h1>KWITANSI</h1>
                <table>
                    <tr><td style="width: 34%" class="muted">No. kwitansi</td><td>{{ $payment->receipt_number }}</td></tr>
                    <tr><td class="muted">No. tagihan</td><td>{{ $invoice->number }}</td></tr>
                    <tr><td class="muted">Telah terima dari</td><td>Orang tua/wali {{ $invoice->student->name }} (NIS {{ $invoice->student->nis }})</td></tr>
                    <tr><td class="muted">Untuk pembayaran</td><td>{{ $invoice->feeType->name }} {{ $invoice->period_label }}</td></tr>
                    <tr><td class="muted">Tanggal bayar</td><td>{{ $payment->paid_on->locale('id')->translatedFormat('j F Y') }}</td></tr>
                    <tr><td class="muted">Metode</td><td>{{ $payment->method->label() }}</td></tr>
                </table>
            </td>
            <td class="right">
                <div class="muted">Jumlah dibayar</div>
                <div class="total">{{ $rupiah($payment->amount) }}</div>
                <br>
                <div class="muted">Total tagihan {{ $rupiah($invoice->total) }}</div>
                <div class="muted">Sisa setelah pembayaran ini {{ $rupiah(max(0, $invoice->total - $invoice->paid_amount)) }}</div>
                <br>
                @if ($invoice->paid_amount >= $invoice->total)
                    <span class="stamp">LUNAS</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 10px">
        @foreach ($invoice->items as $item)
            <tr><td>{{ $item->description }}</td><td class="right">{{ $rupiah($item->amount) }}</td></tr>
        @endforeach
    </table>

    <p class="muted" style="margin-top: 14px">
        Diverifikasi oleh {{ $payment->verifier?->name ?? '-' }} pada {{ $payment->verified_at?->locale('id')->translatedFormat('j F Y H.i') }}.
        Dokumen ini dibuat otomatis dan sah tanpa tanda tangan.
    </p>
</body>
</html>
