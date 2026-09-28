import { ChevronLeft, ChevronRight, Download, X } from 'lucide-react';
import { useCallback, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/Components/ui/dialog';

export type LightboxImage = { url: string; name: string };

type Props = {
    images: LightboxImage[];
    /** Indeks gambar yang dibuka; null = tertutup. */
    index: number | null;
    onIndexChange: (index: number | null) => void;
    title?: string;
};

/**
 * Pratinjau gambar layar penuh dengan navigasi ←/→ (tombol & keyboard).
 */
export function Lightbox({ images, index, onIndexChange, title }: Props) {
    const open = index !== null && images[index] !== undefined;
    const go = useCallback(
        (delta: number) => {
            if (index === null || images.length === 0) {
                return;
            }

            onIndexChange((index + delta + images.length) % images.length);
        },
        [index, images.length, onIndexChange],
    );

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'ArrowRight') {
                go(1);
            } else if (event.key === 'ArrowLeft') {
                go(-1);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [open, go]);

    const image = open ? images[index] : null;

    return (
        <Dialog
            open={open}
            onOpenChange={(value) => !value && onIndexChange(null)}
        >
            <DialogContent
                className="max-w-[calc(100vw-1rem)] gap-0 border-0 bg-slate-950 p-0 text-white sm:max-w-5xl [&>button]:hidden"
                aria-describedby="lightbox-caption"
            >
                <DialogTitle className="sr-only">
                    {title ?? 'Pratinjau gambar'}
                </DialogTitle>
                {image && (
                    <>
                        <div className="relative flex max-h-[80vh] min-h-64 items-center justify-center">
                            <img
                                src={image.url}
                                alt={image.name}
                                className="max-h-[80vh] w-auto object-contain"
                            />
                            {images.length > 1 && (
                                <>
                                    <button
                                        type="button"
                                        onClick={() => go(-1)}
                                        className="absolute top-1/2 left-2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 hover:bg-black/70 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                                        aria-label="Sebelumnya"
                                    >
                                        <ChevronLeft className="size-5" />
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => go(1)}
                                        className="absolute top-1/2 right-2 flex size-10 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 hover:bg-black/70 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                                        aria-label="Berikutnya"
                                    >
                                        <ChevronRight className="size-5" />
                                    </button>
                                </>
                            )}
                        </div>
                        <div className="flex items-center gap-3 px-4 py-3 text-sm">
                            <DialogDescription
                                id="lightbox-caption"
                                className="min-w-0 flex-1 truncate text-slate-300"
                            >
                                {title ? `${title} · ` : ''}
                                {index !== null &&
                                    `${index + 1}/${images.length}`}
                            </DialogDescription>
                            <a
                                href={image.url}
                                download={image.name}
                                className="flex items-center gap-1 text-slate-200 hover:text-white"
                            >
                                <Download className="size-4" /> Unduh
                            </a>
                            <button
                                type="button"
                                onClick={() => onIndexChange(null)}
                                className="flex items-center gap-1 text-slate-200 hover:text-white"
                                aria-label="Tutup"
                            >
                                <X className="size-4" />
                            </button>
                        </div>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
