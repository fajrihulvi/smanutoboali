import { ImageOff } from 'lucide-react';
import { FileUploader } from '@/Components/shared/FileUploader';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { Button } from '@/Components/ui/button';

type Props = {
    name: string;
    currentUrl: string | null | undefined;
    file: File | null;
    remove: boolean;
    onFileChange: (file: File | null) => void;
    onRemoveChange: (remove: boolean) => void;
    error?: string;
};

/**
 * Foto profil: tampilkan foto sekarang (bisa dihapus) atau uploader.
 */
export function PhotoField({
    name,
    currentUrl,
    file,
    remove,
    onFileChange,
    onRemoveChange,
    error,
}: Props) {
    if (currentUrl && !remove && !file) {
        return (
            <div className="flex items-center gap-4">
                <PersonAvatar
                    name={name}
                    photoUrl={currentUrl}
                    className="size-20"
                />
                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => onRemoveChange(true)}
                    >
                        <ImageOff />
                        Hapus foto
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <FileUploader
                value={file ? [file] : []}
                onChange={(files) => onFileChange(files[0] ?? null)}
                accept={['image/jpeg', 'image/png', 'image/webp']}
                maxSizeKb={2048}
                hint="JPG, PNG, atau WebP · maks 2 MB"
                error={error}
            />
            {remove && (
                <p className="text-xs text-amber-700">
                    Foto akan dihapus saat disimpan.{' '}
                    <button
                        type="button"
                        className="font-medium underline"
                        onClick={() => onRemoveChange(false)}
                    >
                        Batalkan
                    </button>
                </p>
            )}
        </div>
    );
}
