<?php

namespace App\Modules\Announcement\Database\Seeders;

use App\Models\User;
use App\Modules\Academic\Models\Classroom;
use App\Modules\Announcement\Actions\SaveAnnouncementAction;
use App\Modules\Announcement\Enums\AnnouncementAudience;
use App\Modules\Announcement\Models\Announcement;
use App\Support\ActivePeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Pengumuman demo: satu disematkan untuk semua, satu khusus guru, satu untuk satu rombel,
 * satu terjadwal, dan satu draf.
 */
class AnnouncementDemoSeeder extends Seeder
{
    public function run(SaveAnnouncementAction $save, ActivePeriod $period): void
    {
        if (app()->isProduction() || Announcement::query()->exists()) {
            return;
        }

        $operator = User::query()->where('email', 'operator@skoolia.test')->first();
        $kepsek = User::query()->where('email', 'kepsek@skoolia.test')->first();

        if (! $operator || ! $kepsek) {
            return;
        }

        $classroom = Classroom::query()->where('academic_year_id', $period->academicYearId() ?? 0)->orderBy('grade')->orderBy('name')->first();
        $now = CarbonImmutable::now();

        $base = ['roles' => [], 'classroom_ids' => [], 'pinned' => false];

        $save->handle([...$base,
            'title' => 'Libur Hari Maulid Nabi Muhammad SAW',
            'body' => "Diberitahukan kepada seluruh warga sekolah bahwa kegiatan belajar mengajar diliburkan pada hari Senin.\n\nKegiatan belajar kembali seperti biasa pada hari Selasa pukul 07.00.",
            'audience' => AnnouncementAudience::All, 'pinned' => true, 'publish_at' => $now->subDays(2),
        ], $kepsek);

        $save->handle([...$base,
            'title' => 'Rapat dewan guru — evaluasi tengah semester',
            'body' => "Rapat dewan guru dilaksanakan hari Kamis pukul 13.30 di ruang guru.\nMohon membawa rekap nilai sementara.",
            'audience' => AnnouncementAudience::Roles, 'roles' => ['teacher', 'principal'], 'publish_at' => $now->subDay(),
        ], $kepsek);

        if ($classroom) {
            $save->handle([...$base,
                'title' => "Kunjungan museum kelas {$classroom->name}",
                'body' => "Siswa kelas {$classroom->name} berkumpul di sekolah pukul 07.00. Membawa bekal dan air minum.\nBiaya transportasi ditanggung sekolah.",
                'audience' => AnnouncementAudience::Classrooms, 'classroom_ids' => [$classroom->id], 'publish_at' => $now->subHours(3),
            ], $operator);
        }

        $save->handle([...$base,
            'title' => 'Jadwal penilaian tengah semester',
            'body' => 'Penilaian tengah semester dimulai minggu depan. Jadwal lengkap terlampir.',
            'audience' => AnnouncementAudience::All, 'publish_at' => $now->addDays(3)->setTime(7, 0),
        ], $operator);

        $save->handle([...$base,
            'title' => 'Draf: pembagian raport',
            'body' => 'Menunggu kepastian tanggal.',
            'audience' => AnnouncementAudience::All, 'publish_at' => null,
        ], $operator);
    }
}
