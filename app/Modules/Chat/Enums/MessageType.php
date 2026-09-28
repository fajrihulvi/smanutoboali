<?php

namespace App\Modules\Chat\Enums;

enum MessageType: string
{
    case Text = 'TEXT';
    case Image = 'IMAGE';
    case File = 'FILE';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Teks',
            self::Image => 'Gambar',
            self::File => 'Berkas',
        };
    }

    /**
     * Cuplikan untuk daftar percakapan/notifikasi bila pesan tanpa teks.
     */
    public function placeholder(): string
    {
        return match ($this) {
            self::Text => '',
            self::Image => '📷 Foto',
            self::File => '📎 Berkas',
        };
    }
}
