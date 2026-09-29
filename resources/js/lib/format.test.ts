import { describe, expect, it } from 'vitest';
import { formatCompactCurrency, formatPercent } from '@/lib/format';

describe('format', () => {
    it('meringkas rupiah besar', () => {
        expect(formatCompactCurrency(8_500_000).replace(/\s/g, ' ')).toBe(
            'Rp 8,5 jt',
        );
        expect(formatCompactCurrency(null).replace(/\s/g, ' ')).toBe('Rp 0');
    });

    it('memformat persen dengan koma desimal', () => {
        expect(formatPercent(92.5)).toBe('92,5%');
        expect(formatPercent(100)).toBe('100%');
        expect(formatPercent(null)).toBe('—');
    });
});
