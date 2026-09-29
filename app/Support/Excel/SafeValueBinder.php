<?php

namespace App\Support\Excel;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Cegah formula/CSV injection pada ekspor Excel: teks (mis. nama siswa, catatan orang tua)
 * yang diawali `=`, `+`, `-`, `@` tidak boleh menjadi formula. Semua string disimpan sebagai
 * teks; angka tetap angka agar bisa dijumlah.
 */
class SafeValueBinder extends DefaultValueBinder
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    /**
     * Binder PhpSpreadsheet bersifat global (statis). Kembalikan ke bawaan setelah ekspor
     * agar proses yang sama (mis. worker antrean) tidak ikut membaca file impor sebagai teks.
     */
    public static function reset(): void
    {
        Cell::setValueBinder(new DefaultValueBinder);
    }
}
