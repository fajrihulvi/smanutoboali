<?php

namespace App\Modules\Academic\Http\Resources;

use App\Modules\People\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Siswa di daftar "Siswa" guru: identitas singkat + rombel tahun aktif.
 *
 * @mixin Student
 */
class TaughtStudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $classroom = $this->enrollments->first()?->classroom;

        return [
            'public_id' => $this->public_id,
            'nis' => $this->nis,
            'name' => $this->name,
            'gender' => $this->gender->value,
            'classroom' => $classroom ? ['public_id' => $classroom->public_id, 'name' => $classroom->name] : null,
        ];
    }
}
