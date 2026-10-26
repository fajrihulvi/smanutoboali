<?php

namespace App\Modules\GuestBook\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Form buku tamu di website (tanpa login). Waktu kunjungan = saat mengisi.
 */
class PublicGuestVisitRequest extends GuestVisitRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['visited_at'], $rules['remove_photo']);

        // Honeypot: kolom tersembunyi yang hanya diisi bot.
        return [...$rules, 'website' => ['prohibited']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [...parent::messages(), 'website.prohibited' => 'Isian tidak valid.'];
    }

    public function payload(): array
    {
        return [...parent::payload(), 'visited_at' => now()->toDateTimeString()];
    }
}
