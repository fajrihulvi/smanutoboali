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

/** "2026-09" → bulan sebelum/sesudah. */
export function shiftMonth(month: string, delta: number): string {
    const [year, monthIndex] = month.split('-').map(Number);
    const date = new Date(year, monthIndex - 1 + delta, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

/** Semua tanggal (Y-m-d) di bulan tsb, dikelompokkan per minggu Senin–Minggu (null = sel kosong). */
export function monthGrid(month: string): (string | null)[][] {
    const [year, monthIndex] = month.split('-').map(Number);
    const daysInMonth = new Date(year, monthIndex, 0).getDate();
    const firstWeekday = (new Date(year, monthIndex - 1, 1).getDay() + 6) % 7; // Senin = 0
    const cells: (string | null)[] = Array.from(
        { length: firstWeekday },
        () => null,
    );

    for (let day = 1; day <= daysInMonth; day++) {
        cells.push(`${month}-${String(day).padStart(2, '0')}`);
    }

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    const weeks: (string | null)[][] = [];

    for (let i = 0; i < cells.length; i += 7) {
        weeks.push(cells.slice(i, i + 7));
    }

    return weeks;
}
