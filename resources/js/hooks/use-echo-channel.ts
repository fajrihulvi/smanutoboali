import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { loadEcho } from '@/lib/echo';

type Handlers = Record<string, (payload: never) => void>;

type Options = {
    /** Terima notifikasi Laravel (BroadcastNotificationCreated) di channel ini. */
    onNotification?: (payload: never) => void;
};

/**
 * Dengarkan channel privat; otomatis `leave` saat komponen unmount atau nama channel berubah.
 * Handler disimpan di ref sehingga re-render tidak memicu subscribe ulang.
 * Event memakai nama `.NamaEvent` (broadcastAs) atau nama kelas lengkap.
 */
export function useEchoChannel(
    channel: string | null,
    events: Handlers = {},
    options: Options = {},
): void {
    const { realtime } = usePage().props;
    const handlers = useRef({ events, options });

    // Handler terbaru disimpan tanpa subscribe ulang.
    useEffect(() => {
        handlers.current = { events, options };
    });
    const eventNames = Object.keys(events).sort().join('|');

    useEffect(() => {
        const loading = loadEcho(realtime);

        if (loading === null || channel === null) {
            return;
        }

        let cancelled = false;
        let leave: (() => void) | null = null;

        void loading.then((echo) => {
            if (cancelled) {
                return;
            }

            const subscription = echo.private(channel);

            eventNames
                .split('|')
                .filter(Boolean)
                .forEach((name) =>
                    subscription.listen(name, (payload: never) =>
                        handlers.current.events[name]?.(payload),
                    ),
                );

            if (handlers.current.options.onNotification) {
                subscription.notification((payload: never) =>
                    handlers.current.options.onNotification?.(payload),
                );
            }

            leave = () => echo.leave(channel);
        });

        return () => {
            cancelled = true;
            leave?.();
        };
    }, [realtime, channel, eventNames]);
}
