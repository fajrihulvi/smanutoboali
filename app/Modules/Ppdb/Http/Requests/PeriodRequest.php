<?php

namespace App\Modules\Ppdb\Http\Requests;

use App\Modules\Access\Enums\Permission;
use App\Modules\School\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Periode + jalur (kuota) + persyaratan dokumen per jalur dalam satu form.
 */
class PeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::PpdbManage->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', Rule::exists(AcademicYear::class, 'public_id')],
            'grade' => ['required', 'integer', 'min:1', 'max:12'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after:opens_at'],
            'announces_at' => ['nullable', 'date', 'after_or_equal:closes_at'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['boolean'],
            'tracks' => ['required', 'array', 'min:1', 'max:10'],
            'tracks.*.public_id' => ['nullable', 'string'],
            'tracks.*.name' => ['required', 'string', 'max:60', 'distinct'],
            'tracks.*.quota' => ['required', 'integer', 'min:0', 'max:10000'],
            'tracks.*.description' => ['nullable', 'string', 'max:255'],
            'tracks.*.requirements' => ['nullable', 'array', 'max:20'],
            'tracks.*.requirements.*.public_id' => ['nullable', 'string'],
            'tracks.*.requirements.*.name' => ['required', 'string', 'max:100'],
            'tracks.*.requirements.*.description' => ['nullable', 'string', 'max:255'],
            'tracks.*.requirements.*.is_required' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama periode', 'academic_year' => 'tahun ajaran tujuan', 'grade' => 'tingkat tujuan',
            'opens_at' => 'tanggal dibuka', 'closes_at' => 'tanggal ditutup', 'announces_at' => 'tanggal pengumuman',
            'tracks' => 'jalur', 'tracks.*.name' => 'nama jalur', 'tracks.*.quota' => 'kuota',
            'tracks.*.requirements.*.name' => 'nama dokumen',
        ];
    }
}
