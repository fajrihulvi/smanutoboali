<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Enums\TeacherAttendanceValidation;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Validasi lokasi presensi mandiri guru (opsional, lewat pengaturan).
 * Mengembalikan metadata yang disimpan di `check_in_meta` untuk audit.
 */
class TeacherLocationGuard
{
    private const EARTH_RADIUS_M = 6_371_000;

    public function __construct(private AttendanceSettings $settings) {}

    /**
     * @return array{ip: string|null, lat?: float, lng?: float, distance_m?: int}
     *
     * @throws ValidationException
     */
    public function check(?string $ip, ?float $lat, ?float $lng): array
    {
        $meta = ['ip' => $ip];

        if ($lat !== null && $lng !== null) {
            $meta += ['lat' => $lat, 'lng' => $lng];
        }

        switch ($this->settings->teacherValidation()) {
            case TeacherAttendanceValidation::Ip:
                $allowed = $this->settings->teacherAllowedIps();

                if ($ip === null || $allowed === [] || ! IpUtils::checkIp($ip, $allowed)) {
                    throw ValidationException::withMessages([
                        'location' => 'Presensi hanya bisa dilakukan dari jaringan sekolah.',
                    ]);
                }

                break;

            case TeacherAttendanceValidation::Gps:
                $fence = $this->settings->schoolGeofence();

                if ($fence === null) {
                    throw ValidationException::withMessages(['location' => 'Lokasi sekolah belum diatur. Hubungi operator.']);
                }

                if ($lat === null || $lng === null) {
                    throw ValidationException::withMessages(['location' => 'Izinkan akses lokasi di browser untuk presensi.']);
                }

                $distance = (int) round($this->distance($lat, $lng, $fence['lat'], $fence['lng']));
                $meta['distance_m'] = $distance;

                if ($distance > $fence['radius']) {
                    throw ValidationException::withMessages([
                        'location' => "Anda berada ±{$distance} m dari sekolah (maksimal {$fence['radius']} m).",
                    ]);
                }

                break;

            case TeacherAttendanceValidation::None:
                break;
        }

        return $meta;
    }

    /**
     * Jarak Haversine (meter).
     */
    public function distance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_M * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
