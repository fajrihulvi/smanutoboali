/**
 * Validasi & normalisasi isian nilai 0–100 (koma desimal diterima).
 * '' → null (kosongkan), angka valid → number, selain itu → undefined (tidak valid).
 */
export function parseScore(raw: string): number | null | undefined {
    const trimmed = raw.trim();

    if (trimmed === '') {
        return null;
    }

    if (!/^\d{1,3}([.,]\d{1,2})?$/.test(trimmed)) {
        return undefined;
    }

    const value = Number(trimmed.replace(',', '.'));

    return value >= 0 && value <= 100 ? value : undefined;
}

/** 85 → "85", 85.5 → "85,5" (format tampilan Indonesia). */
export function formatScore(value: number | null): string {
    if (value === null) {
        return '';
    }

    return Number.isInteger(value)
        ? String(value)
        : String(value).replace('.', ',');
}
