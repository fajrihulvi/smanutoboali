import type { ReactNode } from 'react';

/**
 * Tampilan tabel data grafik (aksesibilitas: nilai bisa dibaca tanpa hover).
 */
export function ChartTable({
    caption,
    headers,
    rows,
}: {
    caption: string;
    headers: string[];
    rows: ReactNode[][];
}) {
    return (
        <details className="mt-3 text-sm">
            <summary className="cursor-pointer text-xs font-medium text-brand-600 hover:text-brand-700">
                Tampilkan tabel
            </summary>
            <div className="mt-2 max-h-64 overflow-auto rounded-lg border border-slate-200">
                <table className="w-full text-left text-xs">
                    <caption className="sr-only">{caption}</caption>
                    <thead className="sticky top-0 bg-slate-50 text-slate-500">
                        <tr>
                            {headers.map((h) => (
                                <th key={h} className="px-3 py-2 font-medium">
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100 text-slate-700 tabular-nums">
                        {rows.map((row, i) => (
                            <tr key={i}>
                                {row.map((cell, j) => (
                                    <td key={j} className="px-3 py-1.5">
                                        {cell}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </details>
    );
}
