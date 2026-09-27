import { describe, expect, it } from 'vitest';
import { formatDuration, totalMinutes } from '@/Modules/Academic/lib/schedule';
import type { ScheduleEntry } from '@/Modules/Academic/types';

const entry = (start: string, end: string) =>
    ({ start_time: start, end_time: end }) as ScheduleEntry;

describe('durasi jadwal', () => {
    it('menjumlahkan menit semua slot', () => {
        expect(
            totalMinutes([entry('07:30', '08:10'), entry('12:30', '13:50')]),
        ).toBe(120);
        expect(totalMinutes([])).toBe(0);
    });

    it('memformat jam & menit dalam Bahasa Indonesia', () => {
        expect(formatDuration(40)).toBe('40 menit');
        expect(formatDuration(120)).toBe('2 jam');
        expect(formatDuration(200)).toBe('3 jam 20 menit');
    });
});
