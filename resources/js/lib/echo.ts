import type Echo from 'laravel-echo';
import { sendJson } from '@/lib/http';
import type { RealtimeConfig, RealtimeStatus } from '@/types/realtime';

type EchoInstance = Echo<'pusher'>;

let loading: Promise<EchoInstance> | null = null;
let loadedKey: string | null = null;
let status: RealtimeStatus = 'disabled';
const listeners = new Set<(status: RealtimeStatus) => void>();

function setStatus(next: RealtimeStatus): void {
    if (next !== status) {
        status = next;
        listeners.forEach((listener) => listener(next));
    }
}

/**
 * Echo (Pusher, cluster dari server) — singleton yang dimuat lazily (dynamic import) hanya
 * bila realtime aktif, sehingga laravel-echo + pusher-js tidak ikut diunduh saat nonaktif.
 * Konfigurasi dari shared prop `realtime`, bukan VITE_*, agar satu build bisa dipakai di
 * lingkungan dengan kunci berbeda. null = realtime nonaktif.
 */
export function loadEcho(
    config: RealtimeConfig | null,
): Promise<EchoInstance> | null {
    if (config === null || typeof window === 'undefined') {
        return null;
    }

    if (loading !== null && loadedKey === config.key) {
        return loading;
    }

    loadedKey = config.key;
    setStatus('connecting');
    loading = Promise.all([import('laravel-echo'), import('pusher-js')]).then(
        ([{ default: EchoClass }, { default: Pusher }]) => {
            (window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher;

            const echo = new EchoClass({
                broadcaster: 'pusher',
                key: config.key,
                cluster: config.cluster,
                forceTLS: true,
                // Otorisasi channel privat lewat fetch dengan token XSRF terbaru.
                authorizer: (channel: { name: string }) => ({
                    authorize: (
                        socketId: string,
                        callback: (
                            error: Error | null,
                            data: { auth: string } | null,
                        ) => void,
                    ) => {
                        sendJson<{ auth: string }>(
                            '/broadcasting/auth',
                            'POST',
                            { socket_id: socketId, channel_name: channel.name },
                        )
                            .then((data) => callback(null, data))
                            .catch((error: Error) => callback(error, null));
                    },
                }),
            }) as EchoInstance;

            echo.connector.pusher.connection.bind(
                'state_change',
                ({ current }: { current: string }) => {
                    setStatus(
                        current === 'connected'
                            ? 'connected'
                            : current === 'connecting' ||
                                current === 'initialized'
                              ? 'connecting'
                              : 'unavailable',
                    );
                },
            );

            return echo;
        },
    );

    loading.catch(() => setStatus('unavailable'));

    return loading;
}

export function realtimeStatus(): RealtimeStatus {
    return status;
}

/**
 * Berlangganan perubahan status koneksi; mengembalikan fungsi berhenti.
 */
export function onRealtimeStatus(
    listener: (status: RealtimeStatus) => void,
): () => void {
    listeners.add(listener);

    return () => listeners.delete(listener);
}
