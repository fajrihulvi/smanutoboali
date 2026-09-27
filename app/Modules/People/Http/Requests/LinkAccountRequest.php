<?php

namespace App\Modules\People\Http\Requests;

use App\Modules\People\Models\Guardian;
use App\Modules\People\Models\Teacher;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Buat/tautkan akun login untuk guru atau orang tua yang belum punya akun.
 */
class LinkAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $person = $this->route('teacher') ?? $this->route('guardian');

        return ($person instanceof Teacher || $person instanceof Guardian)
            && ($this->user()?->can('update', $person) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['account_email' => ['required', 'email', 'max:255']];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['account_email' => 'email akun'];
    }
}
