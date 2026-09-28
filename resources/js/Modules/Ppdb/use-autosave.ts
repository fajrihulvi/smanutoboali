import { useCallback, useEffect, useRef, useState } from 'react';
import { HttpError, sendJson } from '@/lib/http';

export type SaveState = 'idle' | 'saving' | 'saved' | 'error';

/**
 * Simpan draf otomatis (debounce) setiap `payload` berubah, plus `saveNow(complete)` untuk
 * tombol Lanjut (validasi penuh di server). Error validasi dikembalikan per field.
 */
export function useAutosave(
    url: string,
    payload: Record<string, unknown>,
    enabled: boolean,
    delay = 800,
) {
    const [state, setState] = useState<SaveState>('idle');
    const [savedAt, setSavedAt] = useState<string | null>(null);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const serialized = JSON.stringify(payload);
    const first = useRef(true);

    const save = useCallback(
        async (complete: boolean): Promise<boolean> => {
            setState('saving');

            try {
                const res = await sendJson<{ saved_at: string }>(url, 'PUT', {
                    ...JSON.parse(serialized),
                    complete,
                });
                setErrors({});
                setSavedAt(res.saved_at);
                setState('saved');

                return true;
            } catch (error) {
                setErrors(error instanceof HttpError ? error.errors : {});
                setState('error');

                return false;
            }
        },
        [url, serialized],
    );

    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        if (!enabled) {
            return;
        }

        const timer = window.setTimeout(() => void save(false), delay);

        return () => window.clearTimeout(timer);
    }, [serialized, enabled, delay, save]);

    return { state, savedAt, errors, saveNow: save };
}
