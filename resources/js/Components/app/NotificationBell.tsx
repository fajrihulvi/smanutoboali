import { Link, router, usePage } from '@inertiajs/react';
import { Bell, BellOff, CheckCheck } from 'lucide-react';
import { useEffect, useState } from 'react';
import { NOTIFICATION_EVENT } from '@/Components/app/RealtimeBridge';
import type { RealtimeNotification } from '@/Components/app/RealtimeBridge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Skeleton } from '@/Components/ui/skeleton';
import { formatRelative } from '@/lib/format';
import { requestJson } from '@/lib/http';
import { cn } from '@/lib/utils';

type NotificationItem = {
    id: string;
    title: string;
    body: string | null;
    url: string | null;
    read_at: string | null;
    created_at: string;
};

type RecentResponse = { data: NotificationItem[] };

/**
 * Lonceng notifikasi. Badge dari shared prop notifications.unread (dimuat ulang oleh
 * RealtimeBridge); daftar diambil saat dropdown dibuka dan ditambah otomatis saat notifikasi realtime tiba.
 */
export function NotificationBell({ className }: { className?: string }) {
    const { notifications } = usePage().props;
    const [items, setItems] = useState<NotificationItem[] | null>(null);
    const [failed, setFailed] = useState(false);

    const count = notifications?.unread ?? 0;

    useEffect(() => {
        const onRealtime = (event: Event) => {
            const detail = (event as CustomEvent<RealtimeNotification>).detail;

            setItems((current) =>
                current === null
                    ? null
                    : [
                          {
                              id: detail.id,
                              title: detail.title ?? 'Notifikasi',
                              body: detail.body ?? null,
                              url: detail.url ?? null,
                              read_at: null,
                              created_at: new Date().toISOString(),
                          },
                          ...current,
                      ],
            );
        };

        window.addEventListener(NOTIFICATION_EVENT, onRealtime);

        return () => window.removeEventListener(NOTIFICATION_EVENT, onRealtime);
    }, []);

    const load = async () => {
        setFailed(false);

        try {
            const response = await requestJson<RecentResponse>(
                '/notifikasi/terbaru',
            );
            setItems(response.data);
        } catch {
            setFailed(true);
        }
    };

    const refreshBadge = () => router.reload({ only: ['notifications'] });

    const open = async (item: NotificationItem) => {
        if (!item.read_at) {
            await requestJson(`/notifikasi/${item.id}/dibaca`, 'POST').catch(
                () => null,
            );
            refreshBadge();
        }

        if (item.url) {
            router.visit(item.url);
        } else {
            void load();
        }
    };

    const markAll = async () => {
        await requestJson('/notifikasi/dibaca-semua', 'POST').catch(() => null);
        setItems(
            (current) =>
                current?.map((item) => ({
                    ...item,
                    read_at: item.read_at ?? new Date().toISOString(),
                })) ?? null,
        );
        refreshBadge();
    };

    return (
        <DropdownMenu
            onOpenChange={(isOpen) => {
                if (isOpen) {
                    void load();
                }
            }}
        >
            <DropdownMenuTrigger
                className={cn(
                    'relative inline-flex size-9 items-center justify-center rounded-full text-slate-500 transition-colors duration-150 hover:bg-brand-50 hover:text-brand-700',
                    className,
                )}
                aria-label={
                    count > 0
                        ? `Notifikasi, ${count} belum dibaca`
                        : 'Notifikasi'
                }
            >
                <Bell className="size-5" />
                {count > 0 && (
                    <span className="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] leading-4 font-semibold text-white tabular-nums ring-2 ring-white">
                        {count > 99 ? '99+' : count}
                    </span>
                )}
            </DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                className="w-[min(20rem,calc(100vw-2rem))]"
            >
                <div className="flex items-center justify-between gap-2 pr-1">
                    <DropdownMenuLabel>Notifikasi</DropdownMenuLabel>
                    {count > 0 && (
                        <button
                            type="button"
                            onClick={() => void markAll()}
                            className="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-50"
                        >
                            <CheckCheck className="size-3.5" />
                            Tandai semua dibaca
                        </button>
                    )}
                </div>
                <DropdownMenuSeparator />

                {items === null && !failed && (
                    <div className="space-y-3 p-3" aria-busy="true">
                        <Skeleton className="h-4 w-3/4" />
                        <Skeleton className="h-3 w-1/2" />
                        <Skeleton className="h-4 w-2/3" />
                    </div>
                )}

                {failed && (
                    <p className="px-4 py-6 text-center text-sm text-rose-600">
                        Gagal memuat notifikasi.
                    </p>
                )}

                {items?.length === 0 && (
                    <div className="flex flex-col items-center gap-2 px-4 py-8 text-center">
                        <BellOff className="size-6 text-brand-300" />
                        <p className="text-sm text-slate-500">
                            Belum ada notifikasi.
                        </p>
                    </div>
                )}

                {items && items.length > 0 && (
                    <div className="max-h-96 overflow-y-auto">
                        {items.map((item) => (
                            <DropdownMenuItem
                                key={item.id}
                                onSelect={() => void open(item)}
                                className="flex items-start gap-3 px-3 py-2.5"
                            >
                                <span
                                    className={cn(
                                        'mt-1.5 size-2 shrink-0 rounded-full',
                                        item.read_at
                                            ? 'bg-transparent'
                                            : 'bg-brand-500',
                                    )}
                                    aria-hidden
                                />
                                <span className="min-w-0 flex-1">
                                    <span
                                        className={cn(
                                            'block text-sm',
                                            item.read_at
                                                ? 'text-slate-600'
                                                : 'font-semibold text-slate-900',
                                        )}
                                    >
                                        {item.title}
                                    </span>
                                    {item.body && (
                                        <span className="mt-0.5 line-clamp-2 block text-xs text-slate-500">
                                            {item.body}
                                        </span>
                                    )}
                                    <span className="mt-1 block text-[11px] text-slate-400">
                                        {formatRelative(item.created_at)}
                                    </span>
                                </span>
                            </DropdownMenuItem>
                        ))}
                    </div>
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem
                    asChild
                    className="justify-center text-sm font-medium text-brand-700"
                >
                    <Link href="/notifikasi">Lihat semua notifikasi</Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
