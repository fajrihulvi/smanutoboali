<?php

namespace App\Modules\Finance\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

/**
 * Catat pembayaran (operator, bukti opsional) atau unggah bukti transfer (orang tua, bukti wajib).
 * Otorisasi dilakukan di controller (policy manage/pay).
 */
class PaymentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $proofRequired = $this->routeIs('guardian.*');

        return [
            'amount' => ['required', 'integer', 'min:1'],
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
            'proof' => [$proofRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['amount' => 'jumlah', 'paid_on' => 'tanggal bayar', 'note' => 'catatan', 'proof' => 'bukti transfer'];
    }

    public function paidOn(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->string('paid_on')->value());
    }

    public function proof(): ?UploadedFile
    {
        $file = $this->file('proof');

        return $file instanceof UploadedFile ? $file : null;
    }
}
