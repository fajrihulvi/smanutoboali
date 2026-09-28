import {
    format,
    isSameDay,
    isToday,
    isYesterday,
    parseISO,
    subDays,
} from 'date-fns';
import { id } from 'date-fns/locale';
import type { UiMessage } from '@/Modules/Chat/types';

/** Batas lampiran chat (sama dengan server). */
export const MAX_ATTACHMENT_BYTES = 5 * 1024 * 1024;
export const ATTACHMENT_TYPES = [
    'image/jpeg',
    'image/png',
    'image/webp',
    'application/pdf',
];

/**
 * Gabungkan pesan (dari server/broadcast) ke daftar: ganti yang public_id-nya sama,
 * tambahkan yang baru, lalu urutkan kronologis. Pesan lokal (sending/failed) tetap di akhir.
 */
export function mergeMessages(
    current: UiMessage[],
    incoming: UiMessage[],
): UiMessage[] {
    const byId = new Map<string, UiMessage>();
    const pending: UiMessage[] = [];

    for (const message of current) {
        if (message.status) {
            pending.push(message);
        } else {
            byId.set(message.public_id, message);
        }
    }

    for (const message of incoming) {
        const existing = byId.get(message.public_id);
        // Payload broadcast tidak membawa is_mine/read/can → pertahankan nilai lama.
        byId.set(message.public_id, { ...existing, ...message });
    }

    const settled = [...byId.values()].sort((a, b) =>
        a.created_at === b.created_at
            ? a.public_id.localeCompare(b.public_id)
            : a.created_at.localeCompare(b.created_at),
    );

    return [...settled, ...pending];
}

/**
 * Tandai pesan milik saya sebagai dibaca sampai (termasuk) pesan `lastRead`.
 * Bila `lastRead` tidak ada di daftar, tidak ada yang berubah.
 */
export function markReadUpTo(
    messages: UiMessage[],
    lastRead: string,
): UiMessage[] {
    const index = messages.findIndex((m) => m.public_id === lastRead);

    if (index === -1) {
        return messages;
    }

    return messages.map((message, i) =>
        i <= index && message.is_mine && !message.read
            ? { ...message, read: true }
            : message,
    );
}

/** Label pemisah tanggal: "Hari ini", "Kemarin", atau "Senin, 28 September 2026". */
export function dayLabel(iso: string, now: Date = new Date()): string {
    const date = parseISO(iso);

    if (isSameDay(date, now)) {
        return 'Hari ini';
    }

    if (isSameDay(date, subDays(now, 1))) {
        return 'Kemarin';
    }

    return format(date, 'EEEE, d MMMM yyyy', { locale: id });
}

/** Perlu pemisah tanggal sebelum pesan ke-i? */
export function startsNewDay(messages: UiMessage[], index: number): boolean {
    if (index === 0) {
        return true;
    }

    return !isSameDay(
        parseISO(messages[index - 1].created_at),
        parseISO(messages[index].created_at),
    );
}

/** "14.30" */
export function timeLabel(iso: string): string {
    return format(parseISO(iso), 'HH.mm', { locale: id });
}

/** Waktu singkat untuk daftar percakapan: jam (hari ini), "Kemarin", atau tanggal. */
export function listTimeLabel(iso: string): string {
    const date = parseISO(iso);

    if (isToday(date)) {
        return format(date, 'HH.mm');
    }

    if (isYesterday(date)) {
        return 'Kemarin';
    }

    return format(date, 'd MMM', { locale: id });
}

/** Validasi lampiran di klien; null = valid. */
export function attachmentError(file: File): string | null {
    if (!ATTACHMENT_TYPES.includes(file.type)) {
        return 'Lampiran harus gambar (JPG/PNG/WebP) atau PDF.';
    }

    if (file.size > MAX_ATTACHMENT_BYTES) {
        return 'Ukuran lampiran maksimal 5 MB.';
    }

    return null;
}
