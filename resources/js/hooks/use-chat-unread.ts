import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { CHAT_UNREAD_EVENT } from '@/lib/chat-events';

/**
 * Total chat belum dibaca: nilai awal dari shared prop `chat.unread`, lalu diperbarui
 * realtime lewat event DOM (RealtimeBridge / halaman chat).
 */
export function useChatUnread(): number {
    const shared = usePage().props.chat?.unread ?? 0;
    const [total, setTotal] = useState(shared);
    const [lastShared, setLastShared] = useState(shared);

    // Kunjungan Inertia baru membawa angka terbaru dari server.
    if (shared !== lastShared) {
        setLastShared(shared);
        setTotal(shared);
    }

    useEffect(() => {
        const onChange = (event: Event) =>
            setTotal((event as CustomEvent<number>).detail);

        window.addEventListener(CHAT_UNREAD_EVENT, onChange);

        return () => window.removeEventListener(CHAT_UNREAD_EVENT, onChange);
    }, []);

    return total;
}
