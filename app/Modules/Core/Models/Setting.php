<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pasangan key–value pengaturan sekolah. Akses lewat SettingsService (ber-cache).
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }
}
