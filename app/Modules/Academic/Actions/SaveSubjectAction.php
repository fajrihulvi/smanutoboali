<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Subject;
use App\Modules\Core\Services\AuditLogger;

class SaveSubjectAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{code: string, name: string, group: string, is_active: bool}  $data
     * @param  bool  $audit  false saat impor massal (dicatat sekali per file oleh ImportSubjectsJob)
     */
    public function handle(array $data, ?Subject $subject = null, bool $audit = true): Subject
    {
        $isNew = $subject === null;
        $subject ??= new Subject;
        $subject->fill($data)->save();

        if (! $audit) {
            return $subject;
        }

        $this->audit->record(
            'academic',
            $isNew ? 'created' : 'updated',
            ($isNew ? 'Menambah' : 'Mengubah')." mata pelajaran {$subject->name}",
            $subject,
            ['attributes' => $data],
        );

        return $subject;
    }
}
