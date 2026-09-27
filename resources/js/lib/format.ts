import { format, formatDistanceToNow, parseISO } from 'date-fns';
import { id } from 'date-fns/locale';

type DateInput = string | Date | null | undefined;

function toDate(value: DateInput): Date | null {
    if (!value) {
        return null;
    }

    return typeof value === 'string' ? parseISO(value) : value;
}

/** 27 Sep 2026 */
export function formatDate(value: DateInput, pattern = 'd MMM yyyy'): string {
    const date = toDate(value);

    return date ? format(date, pattern, { locale: id }) : '—';
}

/** 27 Sep 2026, 14.30 */
export function formatDateTime(value: DateInput): string {
    return formatDate(value, 'd MMM yyyy, HH.mm');
}

/** "3 menit yang lalu" */
export function formatRelative(value: DateInput): string {
    const date = toDate(value);

    return date
        ? formatDistanceToNow(date, { addSuffix: true, locale: id })
        : '—';
}

const currency = new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
});

/** Rp 150.000 — uang disimpan sebagai integer rupiah. */
export function formatCurrency(value: number | null | undefined): string {
    return currency.format(value ?? 0);
}

const number = new Intl.NumberFormat('id-ID');

export function formatNumber(value: number | null | undefined): string {
    return number.format(value ?? 0);
}

/** 1,5 MB */
export function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let size = bytes / 1024;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit++;
    }

    return `${size.toLocaleString('id-ID', { maximumFractionDigits: 1 })} ${units[unit]}`;
}
