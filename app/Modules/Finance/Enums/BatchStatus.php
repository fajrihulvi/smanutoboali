<?php

namespace App\Modules\Finance\Enums;

enum BatchStatus: string
{
    case Queued = 'QUEUED';
    case Running = 'RUNNING';
    case Done = 'DONE';
    case Failed = 'FAILED';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'Menunggu antrean',
            self::Running => 'Diproses',
            self::Done => 'Selesai',
            self::Failed => 'Gagal',
        };
    }
}
