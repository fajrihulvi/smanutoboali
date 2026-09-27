<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Enums\SettingKey;
use App\Modules\Core\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Pengaturan sekolah (key–value) dengan cache. Semua modul membaca lewat sini.
 */
class SettingsService
{
    public const CACHE_KEY = 'skoolia.settings';

    /** @var array<string, mixed>|null */
    private ?array $memo = null;

    /**
     * Semua pengaturan (nilai tersimpan di atas nilai default).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $defaults = [];

        foreach (SettingKey::cases() as $key) {
            $defaults[$key->value] = $key->default();
        }

        try {
            /** @var array<string, mixed> $stored */
            $stored = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()->pluck('value', 'key')->all());
        } catch (Throwable) {
            // Database/tabel belum siap (instalasi awal, build rilis tanpa .env):
            // pakai default, jangan di-cache.
            return $defaults;
        }

        return $this->memo = array_merge($defaults, array_intersect_key($stored, $defaults));
    }

    public function get(SettingKey $key): mixed
    {
        return $this->all()[$key->value];
    }

    /**
     * @param  array<string, mixed>  $values  key = SettingKey::value
     */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            $settingKey = SettingKey::from($key);

            Setting::query()->updateOrCreate(['key' => $settingKey->value], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->memo = null;
    }

    /**
     * Batas ukuran upload (KB), tidak melebihi batas keras konfigurasi.
     */
    public function uploadMaxKb(): int
    {
        $hardMax = (int) config('skoolia.upload.hard_max_kb', 20480);

        return max(1, min((int) $this->get(SettingKey::UploadMaxSizeKb), $hardMax));
    }

    /**
     * Terapkan pengaturan yang memengaruhi konfigurasi runtime (zona waktu).
     */
    public function applyRuntimeConfig(): void
    {
        $timezone = (string) $this->get(SettingKey::Timezone);

        if (array_key_exists($timezone, SettingKey::timezones()) && $timezone !== config('app.timezone')) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }
    }
}
