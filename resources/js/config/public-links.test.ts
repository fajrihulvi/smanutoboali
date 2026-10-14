import { describe, expect, it } from 'vitest';

/**
 * Website publik (`/`, berita, PPDB info, …) dirender Blade, bukan Inertia.
 * <Link> ke sana membuat Inertia menampilkan HTML-nya di dalam modal; pakai <a>.
 */
const sources = import.meta.glob<string>('/resources/js/**/*.tsx', {
    query: '?raw',
    import: 'default',
    eager: true,
});

describe('tautan ke website publik', () => {
    it('tidak memakai <Link> Inertia ke beranda website', () => {
        const offenders = Object.entries(sources)
            .filter(([, code]) => /<Link[^>]*\shref=["'{`]+\/["'`}]/.test(code))
            .map(([path]) => path);

        expect(offenders).toEqual([]);
    });
});
