import { describe, expect, it } from 'vitest';
import { formatScore, parseScore } from '@/Modules/Assessment/lib/score';

describe('parseScore', () => {
    it('menerima 0–100 dengan koma atau titik desimal', () => {
        expect(parseScore('85')).toBe(85);
        expect(parseScore(' 87,5 ')).toBe(87.5);
        expect(parseScore('100')).toBe(100);
        expect(parseScore('0')).toBe(0);
    });

    it('kosong berarti hapus nilai', () => {
        expect(parseScore('')).toBeNull();
        expect(parseScore('   ')).toBeNull();
    });

    it('menolak di luar rentang atau bukan angka', () => {
        expect(parseScore('101')).toBeUndefined();
        expect(parseScore('-5')).toBeUndefined();
        expect(parseScore('8a')).toBeUndefined();
        expect(parseScore('85,555')).toBeUndefined();
    });
});

describe('formatScore', () => {
    it('memakai koma desimal', () => {
        expect(formatScore(85)).toBe('85');
        expect(formatScore(85.5)).toBe('85,5');
        expect(formatScore(null)).toBe('');
    });
});
