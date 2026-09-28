<?php

namespace App\Modules\Chat\Http\Requests;

use App\Modules\Chat\Actions\SendMessageAction;
use App\Modules\Chat\Models\Message;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string', 'max:'.Message::MAX_BODY, 'required_without:file'],
            'file' => ['nullable', 'file', 'max:'.SendMessageAction::MAX_ATTACHMENT_KB, 'mimes:jpg,jpeg,png,webp,pdf'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required_without' => 'Tulis pesan atau lampirkan berkas.',
            'file.max' => 'Ukuran lampiran maksimal 5 MB.',
            'file.mimes' => 'Lampiran harus gambar (JPG/PNG/WebP) atau PDF.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'pesan', 'file' => 'lampiran'];
    }
}
