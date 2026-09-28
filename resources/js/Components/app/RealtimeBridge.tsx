import { router, usePage } from '@inertiajs/react';
import { WifiOff } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { useEchoChannel } from '@/hooks/use-echo-channel';
import { useRealtimeStatus } from '@/hooks/use-realtime-status';
import {
    CHAT_MESSAGE_EVENT,
    emitChatUnread,
    isConversationOpen,
} from '@/lib/chat-events';
import type { ChatMessageNotice } from '@/lib/chat-events';
import { createThrottle } from '@/lib/throttle';

export type RealtimeNotification = {
    id: string;
    title?: string;
    body?: string | null;
    url?: string | null;
};

/** Event DOM agar komponen lain (lonceng) bisa menambah item tanpa store global. */
export const NOTIFICATION_EVENT = 'skoolia:notification';

const refreshBadge = () => router.reload({ only: ['notifications', 'chat'] });

/**
 * Dipasang sekali per layout: notifikasi realtime (toast + badge), fallback muat ulang saat
 * tab kembali fokus / koneksi pulih, dan indikator kecil hanya saat koneksi terputus.
 */
export function RealtimeBridge() {
    const { realtime, auth } = usePage().props;
    const chatBase = `/${auth.roles.find((role) => role.code === auth.activeRole)?.prefix ?? ''}/chat`;
    const status = useRealtimeStatus();
    const throttle = useRef(createThrottle(30_000));
    const wasUnavailable = useRef(false);

    useEchoChannel(
        realtime?.userChannel ?? null,
        {
            // Pesan chat baru: badge selalu diperbarui; toast hanya bila percakapan tidak sedang dibuka.
            '.chat.message': (notice: ChatMessageNotice) => {
                emitChatUnread(notice.unread_total);
                window.dispatchEvent(
                    new CustomEvent<ChatMessageNotice>(CHAT_MESSAGE_EVENT, {
                        detail: notice,
                    }),
                );

                if (notice.muted || isConversationOpen(notice.conversation)) {
                    return;
                }

                toast(`Pesan dari ${notice.sender ?? 'pengguna'}`, {
                    description: notice.preview,
                    action: {
                        label: 'Balas',
                        onClick: () =>
                            router.visit(`${chatBase}/${notice.conversation}`),
                    },
                });
            },
        },
        {
            onNotification: (payload: RealtimeNotification) => {
                toast(payload.title ?? 'Notifikasi baru', {
                    description: payload.body ?? undefined,
                    action: payload.url
                        ? {
                              label: 'Buka',
                              onClick: () =>
                                  router.visit(payload.url as string),
                          }
                        : undefined,
                });
                window.dispatchEvent(
                    new CustomEvent<RealtimeNotification>(NOTIFICATION_EVENT, {
                        detail: payload,
                    }),
                );
                refreshBadge();
            },
        },
    );

    // Fallback: badge dimuat ulang saat tab kembali aktif (berguna bila realtime nonaktif/terputus).
    useEffect(() => {
        const onVisible = () => {
            if (
                document.visibilityState === 'visible' &&
                throttle.current.ready()
            ) {
                refreshBadge();
            }
        };

        document.addEventListener('visibilitychange', onVisible);
        window.addEventListener('focus', onVisible);

        return () => {
            document.removeEventListener('visibilitychange', onVisible);
            window.removeEventListener('focus', onVisible);
        };
    }, []);

    // Koneksi pulih setelah terputus → ambil notifikasi yang mungkin terlewat.
    useEffect(() => {
        if (status === 'unavailable') {
            wasUnavailable.current = true;
        } else if (status === 'connected' && wasUnavailable.current) {
            wasUnavailable.current = false;
            refreshBadge();
        }
    }, [status]);

    if (status !== 'unavailable') {
        return null;
    }

    return (
        <div
            role="status"
            className="fixed bottom-20 left-4 z-40 flex items-center gap-1.5 rounded-full bg-slate-900/90 px-3 py-1.5 text-xs text-white shadow-lg md:bottom-4"
        >
            <WifiOff className="size-3.5" />
            Koneksi realtime terputus — data diperbarui saat halaman aktif
        </div>
    );
}
