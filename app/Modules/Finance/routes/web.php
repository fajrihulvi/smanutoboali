<?php

use App\Modules\Finance\Http\Controllers\Guardian\InvoiceController as GuardianInvoiceController;
use App\Modules\Finance\Http\Controllers\Operator\DiscountController;
use App\Modules\Finance\Http\Controllers\Operator\FeeTypeController;
use App\Modules\Finance\Http\Controllers\Operator\GenerateController;
use App\Modules\Finance\Http\Controllers\Operator\InvoiceController;
use App\Modules\Finance\Http\Controllers\Operator\VerificationController;
use App\Modules\Finance\Http\Controllers\ReceiptController;
use App\Modules\Finance\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Finance routes — keuangan dasar (S24)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active.role:operator', 'permission:finance.manage'])
    ->prefix('operator/keuangan')->name('operator.finance.')
    ->group(function () {
        Route::redirect('/', '/operator/keuangan/tagihan')->name('home');

        Route::get('tagihan', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('tagihan/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('tagihan/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('tagihan/{invoice}/batal', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('tagihan/{invoice}/pembayaran', [InvoiceController::class, 'storePayment'])->name('invoices.payments.store');

        Route::get('buat', [GenerateController::class, 'create'])->name('generate.create');
        Route::post('buat/preview', [GenerateController::class, 'preview'])->name('generate.preview');
        Route::post('buat', [GenerateController::class, 'store'])->name('generate.store');

        Route::get('verifikasi', [VerificationController::class, 'index'])->name('verification.index');
        Route::post('pembayaran/{payment}/verifikasi', [VerificationController::class, 'review'])->name('payments.review');
        Route::get('pembayaran/{payment}/kwitansi', ReceiptController::class)->name('payments.receipt');

        Route::get('jenis-biaya', [FeeTypeController::class, 'index'])->name('fee_types.index');
        Route::post('jenis-biaya', [FeeTypeController::class, 'store'])->name('fee_types.store');
        Route::put('jenis-biaya/{feeType}', [FeeTypeController::class, 'update'])->name('fee_types.update');
        Route::delete('jenis-biaya/{feeType}', [FeeTypeController::class, 'destroy'])->name('fee_types.destroy');

        Route::get('potongan', [DiscountController::class, 'index'])->name('discounts.index');
        Route::get('potongan/siswa', [DiscountController::class, 'students'])->name('discounts.students');
        Route::post('potongan', [DiscountController::class, 'store'])->name('discounts.store');
        Route::put('potongan/{discount}', [DiscountController::class, 'update'])->name('discounts.update');
        Route::delete('potongan/{discount}', [DiscountController::class, 'destroy'])->name('discounts.destroy');

        Route::get('rekap', [ReportController::class, 'index'])->defaults('base', '/operator/keuangan/rekap')->name('report');
        Route::get('rekap/tunggakan.xlsx', [ReportController::class, 'exportOutstanding'])->name('report.outstanding');
        Route::get('rekap/penerimaan.xlsx', [ReportController::class, 'exportReceipts'])->name('report.receipts');
    });

Route::middleware(['auth', 'active.role:principal', 'permission:finance.view'])
    ->prefix('kepala-sekolah/keuangan')->name('principal.finance.')
    ->group(function () {
        Route::get('/', [ReportController::class, 'index'])->defaults('base', '/kepala-sekolah/keuangan')->name('report');
        Route::get('tunggakan.xlsx', [ReportController::class, 'exportOutstanding'])->name('report.outstanding');
        Route::get('penerimaan.xlsx', [ReportController::class, 'exportReceipts'])->name('report.receipts');
    });

Route::middleware(['auth', 'active.role:guardian', 'permission:finance.view'])
    ->prefix('orang-tua/keuangan')->name('guardian.finance.')
    ->group(function () {
        Route::get('/', [GuardianInvoiceController::class, 'index'])->name('index');
        Route::get('{invoice}', [GuardianInvoiceController::class, 'show'])->name('show');
        Route::post('{invoice}/bukti', [GuardianInvoiceController::class, 'storeProof'])->name('proof.store');
        Route::get('pembayaran/{payment}/kwitansi', ReceiptController::class)->name('payments.receipt');
    });
