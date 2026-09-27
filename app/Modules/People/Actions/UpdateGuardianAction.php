<?php

namespace App\Modules\People\Actions;

use App\Modules\Core\Services\AuditLogger;
use App\Modules\People\Models\Guardian;

/**
 * Ubah data orang tua. Dipakai operator (semua kolom) dan orang tua sendiri (kolom terbatas).
 */
class UpdateGuardianAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Guardian $guardian, array $data): Guardian
    {
        $guardian->fill($data);
        $dirty = $guardian->getDirty();
        $original = array_intersect_key($guardian->getOriginal(), $dirty);
        $guardian->save();

        if ($dirty !== []) {
            // NIK hanya dicatat sebagai "berubah", nilainya tidak disimpan di audit.
            $redact = fn (array $values) => array_key_exists('nik', $values) ? ['nik' => '(diubah)'] + array_diff_key($values, ['nik' => true]) : $values;

            $this->audit->record('people', 'updated', "Mengubah data orang tua {$guardian->name}", $guardian, [
                'old' => $redact($original),
                'attributes' => $redact($dirty),
            ]);
        }

        return $guardian;
    }
}
