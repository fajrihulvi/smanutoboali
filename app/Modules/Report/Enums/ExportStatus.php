<?php

namespace App\Modules\Report\Enums;

enum ExportStatus: string
{
    case Pending = 'PENDING';
    case Processing = 'PROCESSING';
    case Done = 'DONE';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu antrean',
            self::Processing => 'Diproses',
            self::Done => 'Siap diunduh',
            self::Failed => 'Gagal',
        };
    }
}
