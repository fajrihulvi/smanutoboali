<?php

namespace App\Modules\Core\Enums;

enum FileVisibility: string
{
    /** Disimpan di disk 'private', hanya lewat /files/{public_id} + FilePolicy. */
    case Private = 'private';

    /** Disimpan di disk 'public' (logo, gambar website). */
    case Public = 'public';

    public function disk(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Private => 'Privat',
            self::Public => 'Publik',
        };
    }
}
