import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { loadEcho, onRealtimeStatus, realtimeStatus } from '@/lib/echo';
import type { RealtimeStatus } from '@/types/realtime';

/**
 * Status koneksi realtime ('disabled' bila tanpa kunci Pusher / tamu).
 */
export function useRealtimeStatus(): RealtimeStatus {
    const { realtime } = usePage().props;
    const [status, setStatus] = useState<RealtimeStatus>(() =>
        realtime ? realtimeStatus() : 'disabled',
    );

    useEffect(() => {
        if (!realtime) {
            return;
        }

        void loadEcho(realtime);

        return onRealtimeStatus(setStatus);
    }, [realtime]);

    return realtime ? status : 'disabled';
}
