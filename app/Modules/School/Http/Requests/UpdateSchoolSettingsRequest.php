<?php

namespace App\Modules\School\Http\Requests;

use App\Modules\Core\Enums\SettingKey;
use App\Modules\School\Enums\SchoolLevel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Halaman Pengaturan Sekolah: profil sekolah + pengaturan sistem.
 */
class UpdateSchoolSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('setting.manage') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $hardMax = (int) config('skoolia.upload.hard_max_kb', 20480);

        return [
            'name' => ['required', 'string', 'max:150'],
            'npsn' => ['nullable', 'digits:8'],
            'level' => ['required', Rule::enum(SchoolLevel::class)],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'principal_name' => ['nullable', 'string', 'max:150'],
            'principal_nip' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\s]+$/'],
            // Validasi MIME asli dilakukan lagi oleh FileService.
            'logo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_logo' => ['boolean'],
            'timezone' => ['required', Rule::in(array_keys(SettingKey::timezones()))],
            'upload_max_size_kb' => ['required', 'integer', 'min:256', "max:{$hardMax}"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama sekolah',
            'npsn' => 'NPSN',
            'level' => 'jenjang',
            'address' => 'alamat',
            'phone' => 'telepon',
            'email' => 'email sekolah',
            'principal_name' => 'nama kepala sekolah',
            'principal_nip' => 'NIP kepala sekolah',
            'logo' => 'logo',
            'timezone' => 'zona waktu',
            'upload_max_size_kb' => 'ukuran maksimum upload',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'principal_nip.regex' => 'NIP hanya boleh berisi angka.',
        ];
    }

    /**
     * @return array{name: string, npsn: string|null, level: string, address: string|null, phone: string|null, email: string|null, principal_name: string|null, principal_nip: string|null}
     */
    public function profile(): array
    {
        $optional = fn (string $key) => $this->filled($key) ? $this->string($key)->trim()->value() : null;

        return [
            'name' => $this->string('name')->trim()->value(),
            'npsn' => $optional('npsn'),
            'level' => $this->string('level')->value(),
            'address' => $optional('address'),
            'phone' => $optional('phone'),
            'email' => $this->filled('email') ? $this->string('email')->trim()->lower()->value() : null,
            'principal_name' => $optional('principal_name'),
            'principal_nip' => $optional('principal_nip'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function systemSettings(): array
    {
        return [
            SettingKey::Timezone->value => $this->string('timezone')->value(),
            SettingKey::UploadMaxSizeKb->value => $this->integer('upload_max_size_kb'),
        ];
    }
}
