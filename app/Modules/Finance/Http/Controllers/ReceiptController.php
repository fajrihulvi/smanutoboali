<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Payment;
use App\Modules\School\Models\School;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Kwitansi PDF per pembayaran terkonfirmasi (orang tua anak itu, operator, kepala sekolah).
 */
class ReceiptController extends Controller
{
    public function __invoke(Payment $payment): Response
    {
        $this->authorize('receipt', $payment);
        $payment->load(['invoice.student', 'invoice.feeType', 'invoice.items', 'verifier']);

        $pdf = Pdf::loadView('finance::receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
            'school' => School::current(),
        ])->setPaper('a5', 'landscape');

        return $pdf->download(Str::slug('kwitansi '.$payment->receipt_number).'.pdf');
    }
}
