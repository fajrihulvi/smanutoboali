<?php

namespace App\Modules\People\Http\Requests;

use App\Modules\People\Models\Guardian;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Tambah & ubah orang tua oleh operator.
 */
class GuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        $guardian = $this->guardian();

        return $guardian
            ? ($this->user()?->can('update', $guardian) ?? false)
            : ($this->user()?->can('create', Guardian::class) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'nik' => ['nullable', 'digits:16'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email' => ['nullable', 'required_if_accepted:create_account', 'email', 'max:150'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'create_account' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'nik' => 'NIK',
            'phone' => 'telepon/WhatsApp',
            'occupation' => 'pekerjaan',
            'address' => 'alamat',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Telepon hanya boleh berisi angka, spasi, + dan -.',
            'email.required_if_accepted' => 'Email wajib diisi untuk membuat akun login.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $optional = fn (string $key) => $this->filled($key) ? $this->string($key)->trim()->value() : null;

        $data = [
            'name' => $this->string('name')->trim()->value(),
            'phone' => $optional('phone'),
            'email' => $this->filled('email') ? $this->string('email')->trim()->lower()->value() : null,
            'occupation' => $optional('occupation'),
            'address' => $optional('address'),
        ];

        // NIK kosong saat ubah = tidak diubah (nilai lama tidak dikirim ke browser).
        if ($this->filled('nik') || ! $this->guardian()) {
            $data['nik'] = $optional('nik');
        }

        return $data;
    }

    private function guardian(): ?Guardian
    {
        $guardian = $this->route('guardian');

        return $guardian instanceof Guardian ? $guardian : null;
    }
}
