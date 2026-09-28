import { router } from '@inertiajs/react';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { cn } from '@/lib/utils';

export type SwitcherChild = {
    public_id: string;
    name: string;
    photo_url: string | null;
};

type Props = {
    items: SwitcherChild[];
    active: string | null;
};

/**
 * Pilih anak di portal orang tua (chip avatar). Anak aktif disimpan di session sehingga
 * berlaku di semua halaman. Tidak tampil bila anak hanya satu.
 */
export function StudentSwitcher({ items, active }: Props) {
    if (items.length < 2) {
        return null;
    }

    return (
        <div
            role="tablist"
            aria-label="Pilih anak"
            className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0"
        >
            {items.map((child) => {
                const selected = child.public_id === active;

                return (
                    <button
                        key={child.public_id}
                        type="button"
                        role="tab"
                        aria-selected={selected}
                        onClick={() =>
                            !selected &&
                            router.post(
                                '/orang-tua/anak-aktif',
                                {
                                    anak: child.public_id,
                                    kembali:
                                        window.location.pathname +
                                        window.location.search,
                                },
                                { preserveScroll: true },
                            )
                        }
                        className={cn(
                            'flex shrink-0 items-center gap-2 rounded-full border py-1 pr-4 pl-1 text-sm font-medium transition-colors duration-150 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none',
                            selected
                                ? 'border-brand-600 bg-brand-600 text-white'
                                : 'border-slate-200 bg-white text-slate-700 hover:border-brand-300',
                        )}
                    >
                        <PersonAvatar
                            name={child.name}
                            photoUrl={child.photo_url}
                            className="size-7"
                        />
                        {child.name.split(' ')[0]}
                    </button>
                );
            })}
        </div>
    );
}
