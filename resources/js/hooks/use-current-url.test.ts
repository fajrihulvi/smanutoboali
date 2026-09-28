import { describe, expect, it } from 'vitest';
import { isSameOrChildPath } from '@/hooks/use-current-url';

describe('isSameOrChildPath', () => {
    it('mengenali halaman yang sama dan turunannya', () => {
        expect(
            isSameOrChildPath('/operator/presensi', '/operator/presensi'),
        ).toBe(true);
        expect(
            isSameOrChildPath(
                '/operator/presensi/rombel/abc',
                '/operator/presensi',
            ),
        ).toBe(true);
        expect(
            isSameOrChildPath(
                '/operator/master-data/siswa',
                '/operator/master-data/',
            ),
        ).toBe(true);
    });

    it('tidak menganggap awalan teks biasa sebagai induk', () => {
        expect(
            isSameOrChildPath('/operator/presensi-guru', '/operator/presensi'),
        ).toBe(false);
        expect(isSameOrChildPath('/guru/kelasku', '/guru/kelas')).toBe(false);
    });
});
