import { lazy, Suspense } from 'react';
import type { ComponentProps } from 'react';
import { Skeleton } from '@/Components/ui/skeleton';

/*
 * TipTap + ProseMirror (±120 kB gzip) dimuat terpisah agar form CMS tampil lebih dulu.
 */
const Editor = lazy(() =>
    import('@/Modules/Cms/Components/RichTextEditor').then((m) => ({
        default: m.RichTextEditor,
    })),
);

export function LazyRichTextEditor(props: ComponentProps<typeof Editor>) {
    return (
        <Suspense
            fallback={
                <div
                    aria-busy="true"
                    aria-label="Memuat editor"
                    className="overflow-hidden rounded-xl border border-input"
                >
                    <Skeleton className="h-10 rounded-none" />
                    <Skeleton className="mt-px h-72 rounded-none opacity-50" />
                </div>
            }
        >
            <Editor {...props} />
        </Suspense>
    );
}
