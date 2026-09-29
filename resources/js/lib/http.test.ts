import { describe, expect, it } from 'vitest';
import { errorMessage, filenameFromDisposition } from '@/lib/http';

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

describe('filenameFromDisposition', () => {
    it('membaca nama file dari header attachment', () => {
        expect(
            filenameFromDisposition(
                'attachment; filename="laporan-penerimaan-2026-09-29.pdf"',
                'x',
            ),
        ).toBe('laporan-penerimaan-2026-09-29.pdf');
        expect(
            filenameFromDisposition(
                "attachment; filename*=UTF-8''rekap%20nilai.xlsx",
                'x',
            ),
        ).toBe('rekap nilai.xlsx');
    });

    it('memakai nama cadangan bila header tidak ada', () => {
        expect(filenameFromDisposition(null, 'unduhan')).toBe('unduhan');
    });
});
