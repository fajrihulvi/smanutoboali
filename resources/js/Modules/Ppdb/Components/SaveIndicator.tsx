import { Check, CloudOff, Loader2 } from 'lucide-react';
import { formatDate } from '@/lib/format';
import type { SaveState } from '@/Modules/Ppdb/use-autosave';

export function SaveIndicator({
    state,
    savedAt,
}: {
    state: SaveState;
    savedAt: string | null;
}) {
    if (state === 'saving') {
        return (
            <span className="flex items-center gap-1 text-xs text-slate-500">
                <Loader2 className="size-3.5 animate-spin" /> Menyimpan…
            </span>
        );
    }

    if (state === 'error') {
        return (
            <span className="flex items-center gap-1 text-xs text-rose-600">
                <CloudOff className="size-3.5" /> Belum tersimpan — periksa
                isian
            </span>
        );
    }

    return savedAt ? (
        <span className="flex items-center gap-1 text-xs text-emerald-700">
            <Check className="size-3.5" /> Draf tersimpan{' '}
            {formatDate(savedAt, 'HH.mm')}
        </span>
    ) : null;
}
