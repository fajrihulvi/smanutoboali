<?php

namespace App\Modules\Cms\Enums;

enum PageType: string
{
    case Profile = 'PROFIL';
    case VisionMission = 'VISI_MISI';
    case Greeting = 'SAMBUTAN';
    case Structure = 'STRUKTUR';
    case Facilities = 'FASILITAS';
    case Contact = 'KONTAK';
    case Custom = 'CUSTOM';

    public function label(): string
    {
        return match ($this) {
            self::Profile => 'Profil',
            self::VisionMission => 'Visi & Misi',
            self::Greeting => 'Sambutan',
            self::Structure => 'Struktur Organisasi',
            self::Facilities => 'Fasilitas',
            self::Contact => 'Kontak',
            self::Custom => 'Halaman custom',
        };
    }

    /**
     * Halaman sistem: dibuat otomatis, slug terkunci, tidak bisa dihapus.
     */
    public function isSystem(): bool
    {
        return $this !== self::Custom;
    }
}
