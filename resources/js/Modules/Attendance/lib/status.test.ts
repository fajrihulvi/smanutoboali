import { describe, expect, it } from 'vitest';
import { monthGrid, shiftMonth } from '@/Modules/Attendance/lib/status';

describe('helper bulan presensi', () => {
    it('menggeser bulan melewati batas tahun', () => {
        expect(shiftMonth('2026-12', 1)).toBe('2027-01');
        expect(shiftMonth('2026-01', -1)).toBe('2025-12');
        expect(shiftMonth('2026-09', 0)).toBe('2026-09');
    });

    it('menyusun grid Senin–Minggu', () => {
        // 1 September 2026 = Selasa → satu sel kosong di depan.
        const weeks = monthGrid('2026-09');

        expect(weeks[0]).toEqual([
            null,
            '2026-09-01',
            '2026-09-02',
            '2026-09-03',
            '2026-09-04',
            '2026-09-05',
            '2026-09-06',
        ]);
        expect(weeks.flat().filter(Boolean)).toHaveLength(30);
        expect(weeks.every((week) => week.length === 7)).toBe(true);
    });
});
