import { router } from '@inertiajs/react';
import { MoreHorizontal } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { toastFirstError } from '@/lib/toast-errors';
import { TRANSITION_LABELS, TRANSITIONS } from '@/Modules/Cms/types';
import type { ContentStatus } from '@/Modules/Cms/types';

type Props = {
    /** URL konten, mis. /operator/cms/halaman/{id}. */
    url: string;
    label: string;
    status: ContentStatus;
};

/**
 * Menu aksi cepat baris: pratinjau & ubah status sesuai transisi yang diizinkan.
 */
export function StatusMenu({ url, label, status }: Props) {
    const change = (next: string) =>
        router.patch(
            `${url}/status`,
            { status: next },
            {
                preserveScroll: true,
                onError: (errors) => toastFirstError(errors),
            },
        );

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    size="icon"
                    className="size-8"
                    aria-label={`Aksi lain untuk ${label}`}
                >
                    <MoreHorizontal className="size-4" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-52">
                <DropdownMenuItem asChild>
                    <a href={`${url}/pratinjau`} target="_blank" rel="noopener">
                        Pratinjau
                    </a>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuLabel className="text-xs text-slate-500">
                    Ubah status
                </DropdownMenuLabel>
                {TRANSITIONS[status.value].map((next) => (
                    <DropdownMenuItem key={next} onSelect={() => change(next)}>
                        {TRANSITION_LABELS[next]}
                    </DropdownMenuItem>
                ))}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
