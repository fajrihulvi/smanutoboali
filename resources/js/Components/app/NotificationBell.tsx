import { usePage } from '@inertiajs/react';
import { Bell, BellOff } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { cn } from '@/lib/utils';

/**
 * Lonceng notifikasi di topbar. Badge dari shared prop notifications.unread;
 * daftar notifikasi diisi di S07, realtime di S19.
 */
export function NotificationBell({ className }: { className?: string }) {
    const { notifications } = usePage().props;
    const unread = notifications?.unread ?? 0;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger
                className={cn(
                    'relative inline-flex size-9 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-brand-50 hover:text-brand-700',
                    className,
                )}
                aria-label={
                    unread > 0
                        ? `Notifikasi, ${unread} belum dibaca`
                        : 'Notifikasi'
                }
            >
                <Bell className="size-5" />
                {unread > 0 && (
                    <span className="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] leading-4 font-semibold text-white tabular-nums ring-2 ring-white">
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80">
                <DropdownMenuLabel>Notifikasi</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <div className="flex flex-col items-center gap-2 px-4 py-8 text-center">
                    <BellOff className="size-6 text-brand-300" />
                    <p className="text-sm text-slate-500">
                        Belum ada notifikasi.
                    </p>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
