/**
 * Helper bulan (format "Y-m") untuk kalender & navigasi bulan.
 */

/** "2026-09" → bulan sebelum/sesudah. */
export function shiftMonth(month: string, delta: number): string {
    const [year, monthIndex] = month.split('-').map(Number);
    const date = new Date(year, monthIndex - 1 + delta, 1);

    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

/** Semua tanggal (Y-m-d) di bulan tsb, dikelompokkan per minggu Senin–Minggu (null = sel kosong). */
export function monthGrid(month: string): (string | null)[][] {
    const [year, monthIndex] = month.split('-').map(Number);
    const daysInMonth = new Date(year, monthIndex, 0).getDate();
    const firstWeekday = (new Date(year, monthIndex - 1, 1).getDay() + 6) % 7; // Senin = 0
    const cells: (string | null)[] = Array.from(
        { length: firstWeekday },
        () => null,
    );

    for (let day = 1; day <= daysInMonth; day++) {
        cells.push(`${month}-${String(day).padStart(2, '0')}`);
    }

    while (cells.length % 7 !== 0) {
        cells.push(null);
    }

    const weeks: (string | null)[][] = [];

    for (let i = 0; i < cells.length; i += 7) {
        weeks.push(cells.slice(i, i + 7));
    }

    return weeks;
}
