import { router } from '@inertiajs/react';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';

/** Pilih semester lewat query `?semester=`, mempertahankan query lain. */
export function SemesterSelect({
    semesters,
    value,
    query = {},
}: {
    semesters: Option[];
    value: string | null;
    query?: Record<string, string | undefined>;
}) {
    return (
        <NativeSelect
            aria-label="Semester"
            className="w-44"
            value={value ?? ''}
            onChange={(event) =>
                router.get(
                    window.location.pathname,
                    { ...query, semester: event.target.value },
                    { preserveScroll: true },
                )
            }
        >
            {semesters.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </NativeSelect>
    );
}
