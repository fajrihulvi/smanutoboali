<?php

namespace App\Modules\Chat\Http\Requests;

use App\Modules\Chat\Models\Conversation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
    /**
     * Otorisasi sebelum validasi (S32): hanya peserta percakapan.
     */
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $conversation instanceof Conversation && ($this->user()?->can('view', $conversation) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'archived' => ['sometimes', 'boolean'],
            'muted' => ['sometimes', 'boolean'],
        ];
    }
}
