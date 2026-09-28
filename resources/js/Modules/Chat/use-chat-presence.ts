import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { loadEcho } from '@/lib/echo';

type PresenceUser = { id: string; name: string };

/**
 * Pengguna yang sedang membuka halaman chat (presence channel `chat.online`).
 * Hanya aktif bila setting status online dinyalakan dan realtime tersedia.
 */
export function useChatPresence(enabled: boolean): Set<string> {
    const { realtime } = usePage().props;
    const [online, setOnline] = useState<Set<string>>(() => new Set());

    useEffect(() => {
        const loading = enabled ? loadEcho(realtime) : null;

        if (loading === null) {
            return;
        }

        let cancelled = false;
        let leave: (() => void) | null = null;

        void loading.then((echo) => {
            if (cancelled) {
                return;
            }

            echo.join('chat.online')
                .here((users: PresenceUser[]) =>
                    setOnline(new Set(users.map((user) => user.id))),
                )
                .joining((user: PresenceUser) =>
                    setOnline((current) => new Set(current).add(user.id)),
                )
                .leaving((user: PresenceUser) =>
                    setOnline((current) => {
                        const next = new Set(current);
                        next.delete(user.id);

                        return next;
                    }),
                );

            leave = () => echo.leave('chat.online');
        });

        return () => {
            cancelled = true;
            leave?.();
        };
    }, [enabled, realtime]);

    return online;
}
