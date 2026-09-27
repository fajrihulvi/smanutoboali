<?php

namespace App\Modules\Core\Http\Requests;

use App\Modules\Core\Enums\SettingKey;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'school_name' => ['required', 'string', 'max:150'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'school_email' => ['nullable', 'string', 'email', 'max:150'],
            'timezone' => ['required', Rule::in(array_keys(SettingKey::timezones()))],
            'upload_max_size_kb' => ['required', 'integer', 'min:256', "max:{$hardMax}"],
            // Validasi MIME asli dilakukan lagi oleh FileService.
            'logo' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
            'remove_logo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school_name' => 'nama sekolah',
            'school_address' => 'alamat',
            'school_phone' => 'telepon',
            'school_email' => 'email sekolah',
            'timezone' => 'zona waktu',
            'upload_max_size_kb' => 'ukuran maksimum upload',
            'logo' => 'logo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_phone.regex' => 'Telepon hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return [
            SettingKey::SchoolName->value => $this->string('school_name')->trim()->value(),
            SettingKey::SchoolAddress->value => $this->filled('school_address') ? $this->string('school_address')->trim()->value() : null,
            SettingKey::SchoolPhone->value => $this->filled('school_phone') ? $this->string('school_phone')->trim()->value() : null,
            SettingKey::SchoolEmail->value => $this->filled('school_email') ? $this->string('school_email')->trim()->lower()->value() : null,
            SettingKey::Timezone->value => $this->string('timezone')->value(),
            SettingKey::UploadMaxSizeKb->value => $this->integer('upload_max_size_kb'),
        ];
    }
}
