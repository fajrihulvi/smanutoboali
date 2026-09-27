import { toast } from 'sonner';

/**
 * Tampilkan error validasi pertama sebagai toast. Dipakai untuk aksi tanpa form
 * (hapus, aktifkan, lepas tautan) yang ditolak server lewat ValidationException.
 */
export function toastFirstError(
    errors: Record<string, string>,
    fallback = 'Aksi tidak dapat diproses.',
): void {
    toast.error(Object.values(errors)[0] ?? fallback);
}
