import { router } from '@inertiajs/react';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { YearOption } from '@/Modules/Academic/types';

/**
 * Pemilih tahun ajaran; mengganti query ?tahun= pada halaman saat ini.
 */
export function YearSelect({
    years,
    value,
}: {
    years: YearOption[];
    value: string | undefined;
}) {
    return (
        <NativeSelect
            aria-label="Tahun ajaran"
            className="w-44"
            value={value ?? ''}
            onChange={(event) =>
                router.get(
                    window.location.pathname,
                    { tahun: event.target.value },
                    { preserveScroll: true },
                )
            }
        >
            {years.map((year) => (
                <option key={year.value} value={year.value}>
                    TA {year.label}
                    {year.is_active ? ' (aktif)' : ''}
                </option>
            ))}
        </NativeSelect>
    );
}
