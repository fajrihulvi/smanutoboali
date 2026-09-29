<?php

namespace App\Modules\Report\Enums;

enum ExportFormat: string
{
    case Pdf = 'pdf';
    case Excel = 'xlsx';

    public function label(): string
    {
        return match ($this) {
            self::Pdf => 'PDF',
            self::Excel => 'Excel',
        };
    }

    public function mime(): string
    {
        return match ($this) {
            self::Pdf => 'application/pdf',
            self::Excel => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        };
    }
}
