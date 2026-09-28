import { describe, expect, it } from 'vitest';
import { errorMessage } from '@/lib/http';

describe('errorMessage', () => {
    it('mengambil pesan validasi pertama', () => {
        expect(
            errorMessage(422, {
                message: 'x',
                errors: { score: ['Nilai maksimal 100.'] },
            }),
        ).toBe('Nilai maksimal 100.');
    });

    it('memakai message untuk error 4xx lain, pesan umum untuk 5xx', () => {
        expect(errorMessage(403, { message: 'Tidak diizinkan.' })).toBe(
            'Tidak diizinkan.',
        );
        expect(errorMessage(500, { message: 'Server Error' })).toBe(
            'Permintaan gagal (500).',
        );
        expect(errorMessage(419, null)).toBe(
            'Sesi berakhir. Muat ulang halaman.',
        );
    });
});
