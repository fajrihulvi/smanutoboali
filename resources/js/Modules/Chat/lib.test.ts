import { describe, expect, it } from 'vitest';
import {
    attachmentError,
    dayLabel,
    markReadUpTo,
    mergeMessages,
    startsNewDay,
} from '@/Modules/Chat/lib';
import type { UiMessage } from '@/Modules/Chat/types';

const msg = (
    public_id: string,
    created_at: string,
    extra: Partial<UiMessage> = {},
): UiMessage => ({
    public_id,
    type: 'TEXT',
    body: public_id,
    deleted: false,
    sender: { public_id: 'u1', name: 'Saya' },
    attachment: null,
    created_at,
    edited_at: null,
    ...extra,
});

describe('mergeMessages', () => {
    it('dedup berdasarkan public_id dan urut kronologis', () => {
        const a = msg('A', '2026-09-28T08:00:00+07:00', { is_mine: true });
        const b = msg('B', '2026-09-28T08:01:00+07:00');

        const merged = mergeMessages([b], [a, b]);

        expect(merged.map((m) => m.public_id)).toEqual(['A', 'B']);
    });

    it('payload broadcast tidak menghapus is_mine/read yang sudah ada', () => {
        const mine = msg('A', '2026-09-28T08:00:00+07:00', {
            is_mine: true,
            read: true,
        });
        const updated = msg('A', '2026-09-28T08:00:00+07:00', {
            body: 'diubah',
        });

        const [result] = mergeMessages([mine], [updated]);

        expect(result).toMatchObject({
            body: 'diubah',
            is_mine: true,
            read: true,
        });
    });

    it('pesan lokal yang masih dikirim tetap di akhir', () => {
        const pending = msg('tmp-1', '2026-09-28T07:00:00+07:00', {
            status: 'sending',
            client_id: 'tmp-1',
        });
        const incoming = msg('B', '2026-09-28T08:00:00+07:00');

        expect(
            mergeMessages([pending], [incoming]).map((m) => m.public_id),
        ).toEqual(['B', 'tmp-1']);
    });
});

describe('markReadUpTo', () => {
    it('hanya pesan saya sampai pesan terakhir dibaca', () => {
        const list = [
            msg('A', '2026-09-28T08:00:00+07:00', { is_mine: true }),
            msg('B', '2026-09-28T08:01:00+07:00', { is_mine: false }),
            msg('C', '2026-09-28T08:02:00+07:00', { is_mine: true }),
        ];

        const result = markReadUpTo(list, 'B');

        expect(result.map((m) => m.read ?? false)).toEqual([
            true,
            false,
            false,
        ]);
        expect(markReadUpTo(list, 'Z')).toBe(list);
    });
});

describe('tanggal', () => {
    const now = new Date('2026-09-28T10:00:00+07:00');

    it('label hari ini, kemarin, dan tanggal lengkap', () => {
        expect(dayLabel('2026-09-28T08:00:00+07:00', now)).toBe('Hari ini');
        expect(dayLabel('2026-09-27T08:00:00+07:00', now)).toBe('Kemarin');
        expect(dayLabel('2026-09-21T08:00:00+07:00', now)).toBe(
            'Senin, 21 September 2026',
        );
    });

    it('pemisah tanggal saat hari berganti', () => {
        const list = [
            msg('A', '2026-09-27T20:00:00+07:00'),
            msg('B', '2026-09-27T21:00:00+07:00'),
            msg('C', '2026-09-28T07:00:00+07:00'),
        ];

        expect(list.map((_, i) => startsNewDay(list, i))).toEqual([
            true,
            false,
            true,
        ]);
    });
});

describe('attachmentError', () => {
    it('menolak tipe lain dan berkas > 5 MB', () => {
        expect(
            attachmentError(
                new File(['x'], 'a.exe', { type: 'application/x-msdownload' }),
            ),
        ).not.toBeNull();
        expect(
            attachmentError(
                new File([new Uint8Array(6 * 1024 * 1024)], 'b.jpg', {
                    type: 'image/jpeg',
                }),
            ),
        ).toBe('Ukuran lampiran maksimal 5 MB.');
        expect(
            attachmentError(
                new File(['x'], 'c.pdf', { type: 'application/pdf' }),
            ),
        ).toBeNull();
    });
});
