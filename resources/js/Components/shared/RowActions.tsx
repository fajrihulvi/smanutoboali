import { Link } from '@inertiajs/react';
import { Pencil, Trash2 } from 'lucide-react';
import { Button } from '@/Components/ui/button';

type Props = {
    /** Nama data untuk label aksesibel, mis. "Budi Santoso". */
    label: string;
    editHref?: string;
    onEdit?: () => void;
    onDelete?: () => void;
};

/**
 * Tombol aksi baris tabel: Ubah (link atau callback) + Hapus.
 */
export function RowActions({ label, editHref, onEdit, onDelete }: Props) {
    return (
        <div className="flex justify-end gap-1">
            {editHref && (
                <Button variant="ghost" size="icon" className="size-8" asChild>
                    <Link
                        href={editHref}
                        aria-label={`Ubah ${label}`}
                        title="Ubah"
                    >
                        <Pencil className="size-4" />
                    </Link>
                </Button>
            )}
            {onEdit && (
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8"
                    aria-label={`Ubah ${label}`}
                    title="Ubah"
                    onClick={onEdit}
                >
                    <Pencil className="size-4" />
                </Button>
            )}
            {onDelete && (
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8 text-slate-400 hover:bg-rose-50 hover:text-rose-600"
                    aria-label={`Hapus ${label}`}
                    title="Hapus"
                    onClick={onDelete}
                >
                    <Trash2 className="size-4" />
                </Button>
            )}
        </div>
    );
}
