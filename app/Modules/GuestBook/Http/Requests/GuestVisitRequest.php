<?php

namespace App\Modules\GuestBook\Http\Requests;

use App\Modules\GuestBook\Enums\GuestCategory;
use App\Modules\GuestBook\Enums\GuestHost;
use App\Modules\GuestBook\Models\GuestVisit;
use App\Modules\GuestBook\Services\WhatsappNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Catatan tamu oleh operator (tambah/ubah). Form publik memakai PublicGuestVisitRequest.
 */
class GuestVisitRequest extends FormRequest
{
    /** Batas unggah foto (KB); foto dikecilkan lagi oleh FileService. */
    public const PHOTO_MAX_KB = 5120;

    public function authorize(): bool
    {
        $visit = $this->route('visit');

        return $visit instanceof GuestVisit
            ? ($this->user()?->can('update', $visit) ?? false)
            : ($this->user()?->can('create', GuestVisit::class) ?? false);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('whatsapp')) {
            $this->merge(['whatsapp' => WhatsappNumber::normalize($this->string('whatsapp')->value())]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'institution' => ['required', 'string', 'max:150'],
            'whatsapp' => ['required', 'string', 'regex:/^08[0-9]{7,12}$/'],
            'category' => ['required', Rule::enum(GuestCategory::class)],
            'purpose' => ['required', 'string', 'max:1000'],
            'host' => ['required', Rule::enum(GuestHost::class)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::PHOTO_MAX_KB],
            'notes' => ['nullable', 'string', 'max:1000'],
            'visited_at' => ['required', 'date', 'before_or_equal:'.now()->addMinutes(5)->toDateTimeString()],
            'remove_photo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.',
            'visited_at.before_or_equal' => 'Waktu kunjungan tidak boleh di masa depan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'institution' => 'instansi asal',
            'whatsapp' => 'nomor WhatsApp',
            'category' => 'kategori tamu',
            'purpose' => 'keperluan',
            'host' => 'bertemu dengan',
            'photo' => 'foto tamu',
            'notes' => 'catatan',
            'visited_at' => 'waktu kunjungan',
        ];
    }

    /**
     * @return array{full_name: string, institution: string, whatsapp: string, category: string, purpose: string, host: string, notes: string|null, visited_at: string}
     */
    public function payload(): array
    {
        $notes = $this->string('notes')->trim()->value();

        return [
            'full_name' => $this->string('full_name')->squish()->value(),
            'institution' => $this->string('institution')->squish()->value(),
            'whatsapp' => $this->string('whatsapp')->value(),
            'category' => $this->string('category')->value(),
            'purpose' => $this->string('purpose')->trim()->value(),
            'host' => $this->string('host')->value(),
            'notes' => $notes !== '' ? $notes : null,
            'visited_at' => $this->string('visited_at')->value(),
        ];
    }
}
