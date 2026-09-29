<?php

namespace App\Modules\Cms\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Teacher;

/**
 * Atur guru yang tampil di website & jabatan yang ditampilkan. Data guru tetap dari modul People.
 */
class UpdateTeacherWebsiteAction
{
    public function __construct(private AuditLogger $audit) {}

    public function handle(Teacher $teacher, bool $show, ?string $title): void
    {
        $teacher->update(['show_on_website' => $show, 'website_title' => $title]);

        $this->audit->record('cms', 'teacher_website_updated', ($show ? 'Menampilkan' : 'Menyembunyikan')." {$teacher->name} di website", $teacher);
    }
}
