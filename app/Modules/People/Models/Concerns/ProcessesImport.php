<?php

namespace App\Modules\People\Models\Concerns;

use App\Modules\People\Enums\ImportStatus;

/**
 * Status proses import (StudentImport, TeacherImport).
 *
 * Import dijalankan tepat setelah respons unggah terkirim, dengan job antrean sebagai cadangan.
 * claimForProcessing() memastikan hanya satu proses yang mengerjakan file yang sama.
 */
trait ProcessesImport
{
    /**
     * Ambil hak memproses secara atomik (Pending → Processing).
     *
     * @return bool false bila import sudah diambil proses lain, sudah selesai, atau sudah dihapus
     */
    public function claimForProcessing(): bool
    {
        $claimed = static::query()
            ->whereKey($this->getKey())
            ->where('status', ImportStatus::Pending->value)
            ->update(['status' => ImportStatus::Processing->value, 'updated_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        $this->refresh();

        return true;
    }

    /**
     * Import yang terhenti di tengah (mis. batas waktu PHP hosting) tidak dibiarkan "Diproses" selamanya.
     * Progres disimpan tiap chunk, jadi updated_at yang lama berarti prosesnya sudah mati.
     */
    public static function failStale(int $minutes = 15): int
    {
        return static::query()
            ->where('status', ImportStatus::Processing->value)
            ->where('updated_at', '<', now()->subMinutes($minutes))
            ->update([
                'status' => ImportStatus::Failed->value,
                'failure_message' => 'Proses terhenti sebelum selesai (batas waktu server). Baris yang sudah masuk tercatat di data; unggah ulang hanya baris yang belum masuk.',
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
