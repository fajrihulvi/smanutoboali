<?php

namespace App\Modules\Assessment\Exports;

use App\Modules\Assessment\Models\GradeComponent;
use App\Modules\Assessment\Services\Gradebook;
use App\Modules\People\Models\Student;
use App\Support\Excel\SafeValueBinder;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

/**
 * Buku nilai sebagai Excel: NIS, Nama, satu kolom per komponen (judul = nama komponen), Nilai Akhir, Predikat.
 * Format yang sama dipakai untuk impor (kolom Nilai Akhir/Predikat diabaikan).
 */
class GradesExport implements FromArray, ShouldAutoSize, WithCustomValueBinder, WithHeadings, WithTitle
{
    public function __construct(private Gradebook $book) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['NIS', 'Nama', ...$this->book->components->map(fn (GradeComponent $c) => $c->name)->all(), 'Nilai Akhir', 'Predikat'];
    }

    /**
     * @return list<list<string|float|int|null>>
     */
    public function array(): array
    {
        return $this->book->students->map(fn (Student $student) => [
            $student->nis,
            $student->name,
            ...$this->book->components->map(fn (GradeComponent $c) => $this->book->score($c, $student))->all(),
            $this->book->finalScore($student),
            $this->book->predicate($student)?->value,
        ])->values()->all();
    }

    public function title(): string
    {
        return mb_substr($this->book->assignment->subject->name.' '.$this->book->assignment->classroom->name, 0, 31);
    }

    /**
     * Teks tidak pernah dibaca sebagai formula (cegah formula injection).
     */
    public function bindValue(Cell $cell, mixed $value): bool
    {
        return (new SafeValueBinder)->bindValue($cell, $value);
    }
}
