import type { AttendanceStatusValue } from '@/Modules/Attendance/types';
import type { StatusVariant } from '@/types';

/** Warna status presensi (token status design system). */
export const STATUS_VARIANT: Record<AttendanceStatusValue, StatusVariant> = {
    HADIR: 'success',
    TERLAMBAT: 'warning',
    IZIN: 'info',
    SAKIT: 'brand',
    ALPA: 'danger',
};

/** Kelas latar sel kalender/matriks per status. */
export const STATUS_CELL: Record<AttendanceStatusValue, string> = {
    HADIR: 'bg-emerald-100 text-emerald-700',
    TERLAMBAT: 'bg-amber-100 text-amber-700',
    IZIN: 'bg-sky-100 text-sky-700',
    SAKIT: 'bg-brand-100 text-brand-700',
    ALPA: 'bg-rose-100 text-rose-700',
};

/** Singkatan satu huruf untuk matriks rekap. */
export const STATUS_SHORT: Record<AttendanceStatusValue, string> = {
    HADIR: 'H',
    TERLAMBAT: 'T',
    IZIN: 'I',
    SAKIT: 'S',
    ALPA: 'A',
};

/** Urutan tampilan status. */
export const STATUS_ORDER: AttendanceStatusValue[] = [
    'HADIR',
    'TERLAMBAT',
    'IZIN',
    'SAKIT',
    'ALPA',
];
