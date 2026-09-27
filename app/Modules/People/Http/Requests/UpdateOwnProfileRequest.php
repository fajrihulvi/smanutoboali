<?php

namespace App\Modules\People\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Profil sendiri guru/orang tua: hanya kolom kontak. Otorisasi (pemilik data)
 * dicek di controller lewat policy updateOwnProfile.
 */
class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            // Hanya relevan untuk guru.
            'photo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_photo' => ['boolean'],
            // Hanya relevan untuk orang tua.
            'occupation' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'telepon',
            'photo' => 'foto',
            'occupation' => 'pekerjaan',
            'address' => 'alamat',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['phone.regex' => 'Telepon hanya boleh berisi angka, spasi, + dan -.'];
    }

    public function optional(string $key): ?string
    {
        return $this->filled($key) ? $this->string($key)->trim()->value() : null;
    }
}
