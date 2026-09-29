import { ImageIcon, Trash2, Undo2 } from 'lucide-react';
import { useEffect, useMemo } from 'react';
import InputError from '@/Components/input-error';
import { Button } from '@/Components/ui/button';

type Props = {
    id: string;
    /** Gambar tersimpan. */
    current: string | null;
    file: File | null;
    removed: boolean;
    onFileChange: (file: File | null) => void;
    onRemovedChange?: (removed: boolean) => void;
    error?: string;
    hint?: string;
};

/**
 * Pilih satu gambar (gambar utama/banner) dengan pratinjau.
 */
export function ImageField({
    id,
    current,
    file,
    removed,
    onFileChange,
    onRemovedChange,
    error,
    hint = 'JPG, PNG, atau WebP · maks 5 MB · diperkecil otomatis.',
}: Props) {
    const localUrl = useMemo(
        () => (file ? URL.createObjectURL(file) : null),
        [file],
    );

    useEffect(
        () => () => {
            if (localUrl) {
                URL.revokeObjectURL(localUrl);
            }
        },
        [localUrl],
    );

    const shown = localUrl ?? (removed ? null : current);

    return (
        <div className="grid gap-2">
            <div className="flex aspect-video items-center justify-center overflow-hidden rounded-xl border border-dashed border-slate-300 bg-slate-50">
                {shown ? (
                    <img
                        src={shown}
                        alt="Pratinjau gambar"
                        className="size-full object-cover"
                    />
                ) : (
                    <ImageIcon className="size-8 text-slate-300" />
                )}
            </div>
            <div className="flex flex-wrap gap-2">
                <Button type="button" variant="outline" size="sm" asChild>
                    <label htmlFor={id} className="cursor-pointer">
                        {shown ? 'Ganti gambar' : 'Pilih gambar'}
                    </label>
                </Button>
                {file && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onFileChange(null)}
                    >
                        <Undo2 />
                        Batal
                    </Button>
                )}
                {!file && current && onRemovedChange && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => onRemovedChange(!removed)}
                    >
                        {removed ? <Undo2 /> : <Trash2 />}
                        {removed ? 'Batal hapus' : 'Hapus'}
                    </Button>
                )}
            </div>
            <input
                id={id}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                className="sr-only"
                onChange={(e) => {
                    onFileChange(e.target.files?.[0] ?? null);
                    e.target.value = '';
                }}
            />
            <p className="text-xs text-slate-500">{hint}</p>
            <InputError message={error} />
        </div>
    );
}
