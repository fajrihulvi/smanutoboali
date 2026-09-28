<?php

namespace App\Modules\Chat\Http\Requests;

use App\Modules\Chat\Models\Message;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $message = $this->route('message');

        return $message instanceof Message && ($this->user()?->can('update', $message) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.Message::MAX_BODY],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['body' => 'pesan'];
    }
}
