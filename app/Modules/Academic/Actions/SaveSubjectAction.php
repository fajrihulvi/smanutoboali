<?php

namespace App\Modules\Academic\Actions;

use App\Modules\Academic\Models\Subject;
use App\Modules\Core\Services\AuditLogger;

class SaveSubjectAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{code: string, name: string, group: string, is_active: bool}  $data
     */
    public function handle(array $data, ?Subject $subject = null): Subject
    {
        $isNew = $subject === null;
        $subject ??= new Subject;
        $subject->fill($data)->save();

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
