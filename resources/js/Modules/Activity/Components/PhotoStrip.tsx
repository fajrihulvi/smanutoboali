import { useState } from 'react';
import { Lightbox } from '@/Components/shared/Lightbox';
import { cn } from '@/lib/utils';
import type { ActivityRow } from '@/Modules/Activity/types';

/**
 * Deretan foto kegiatan (maks 4 tampil, sisanya "+n"); klik → lightbox.
 */
export function PhotoStrip({
    activity,
    className,
}: {
    activity: ActivityRow;
    className?: string;
}) {
    const [index, setIndex] = useState<number | null>(null);
    const photos = activity.photos;

    if (photos.length === 0) {
        return null;
    }

    const shown = photos.slice(0, 4);

    return (
        <>
            <div
                className={cn(
                    'grid gap-1.5',
                    shown.length === 1 ? 'grid-cols-1' : 'grid-cols-2',
                    className,
                )}
            >
                {shown.map((photo, i) => (
                    <button
                        key={photo.public_id}
                        type="button"
                        onClick={() => setIndex(i)}
                        className={cn(
                            'relative overflow-hidden rounded-xl bg-slate-100 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none',
                            shown.length === 1
                                ? 'aspect-video'
                                : 'aspect-square',
                            shown.length === 3 &&
                                i === 0 &&
                                'col-span-2 aspect-video',
                        )}
                        aria-label={`Lihat foto ${i + 1} ${activity.title}`}
                    >
                        <img
                            src={photo.thumb_url}
                            alt=""
                            loading="lazy"
                            className="size-full object-cover"
                        />
                        {i === shown.length - 1 &&
                            photos.length > shown.length && (
                                <span className="absolute inset-0 flex items-center justify-center bg-black/50 text-lg font-semibold text-white">
                                    +{photos.length - shown.length}
                                </span>
                            )}
                    </button>
                ))}
            </div>
            <Lightbox
                images={photos.map((p) => ({ url: p.url, name: p.name }))}
                index={index}
                onIndexChange={setIndex}
                title={activity.title}
            />
        </>
    );
}
