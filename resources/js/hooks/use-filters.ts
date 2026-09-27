import { router } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';

export type FilterValues = Record<string, string | undefined>;

type Options = {
    /** Prop Inertia yang dimuat ulang (partial reload), contoh ['users']. */
    only?: string[];
    debounceMs?: number;
};

function clean(values: FilterValues): Record<string, string> {
    return Object.fromEntries(
        Object.entries(values).filter(
            (entry): entry is [string, string] =>
                entry[1] !== undefined && entry[1] !== '',
        ),
    );
}

/**
 * Sinkronkan filter/pencarian dengan query string (paginasi server-side).
 * Mengubah filter selalu kembali ke halaman 1.
 */
export function useFilters<T extends FilterValues>(
    initial: T,
    { only, debounceMs = 350 }: Options = {},
) {
    const [values, setValues] = useState<T>(initial);
    const current = useRef<T>(initial);
    const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(
        () => () => {
            if (timer.current) {
                clearTimeout(timer.current);
            }
        },
        [],
    );

    const visit = useCallback(
        (next: T) => {
            router.get(window.location.pathname, clean(next), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only,
            });
        },
        [only],
    );

    const update = useCallback(
        (next: T, debounce: boolean) => {
            current.current = next;
            setValues(next);

            if (timer.current) {
                clearTimeout(timer.current);
            }

            if (debounce) {
                timer.current = setTimeout(() => visit(next), debounceMs);
            } else {
                visit(next);
            }
        },
        [visit, debounceMs],
    );

    const set = useCallback(
        <K extends keyof T>(key: K, value: T[K], debounce = false) => {
            update({ ...current.current, [key]: value }, debounce);
        },
        [update],
    );

    const reset = useCallback(() => {
        const empty = Object.fromEntries(
            Object.keys(current.current).map((key) => [key, undefined]),
        ) as T;
        update(empty, false);
    }, [update]);

    const isActive = Object.values(values).some(
        (value) => value !== undefined && value !== '',
    );

    return { values, set, reset, isActive };
}
