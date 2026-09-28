<?php

namespace App\Modules\Chat\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConversationRequest extends FormRequest
{
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
