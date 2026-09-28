import { Head, usePage } from '@inertiajs/react';
import { MessagesSquare } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { useRealtimeStatus } from '@/hooks/use-realtime-status';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import {
    CHAT_MESSAGE_EVENT,
    emitChatUnread,
    setOpenConversation,
} from '@/lib/chat-events';
import { sendJson } from '@/lib/http';
import { cn } from '@/lib/utils';
import { ChatRoom } from '@/Modules/Chat/Components/ChatRoom';
import { ConversationList } from '@/Modules/Chat/Components/ConversationList';
import type { ListTab } from '@/Modules/Chat/Components/ConversationList';
import { NewConversationDialog } from '@/Modules/Chat/Components/NewConversationDialog';
import type { Conversation } from '@/Modules/Chat/types';
import { useChatPresence } from '@/Modules/Chat/use-chat-presence';

type Props = {
    base: string;
    conversations: Conversation[];
    active: Conversation | null;
    presenceEnabled: boolean;
};

/** Fallback tanpa realtime: daftar dimuat ulang tiap 15 detik saat tab terlihat. */
const POLL_MS = 15_000;

export default function ChatIndex({
    base,
    conversations: initial,
    active: initialActive,
    presenceEnabled,
}: Props) {
    const { auth } = usePage().props;
    const me = auth.user?.public_id ?? '';
    const [conversations, setConversations] = useState(initial);
    const [lastInitial, setLastInitial] = useState(initial);
    const [active, setActive] = useState(initialActive);
    const [lastActive, setLastActive] = useState(initialActive);
    const [tab, setTab] = useState<ListTab>('inbox');
    const [search, setSearch] = useState('');
    const [loading, setLoading] = useState(false);
    const [newOpen, setNewOpen] = useState(false);
    const online = useChatPresence(presenceEnabled);
    const status = useRealtimeStatus();
    const query = useRef({ tab, search });

    // Props Inertia baru (kunjungan/partial reload) menggantikan state lokal.
    if (initial !== lastInitial) {
        setLastInitial(initial);
        setConversations(initial);
    }

    if (initialActive !== lastActive) {
        setLastActive(initialActive);
        setActive(initialActive);
    }

    const refresh = useCallback(async () => {
        const { tab: currentTab, search: currentSearch } = query.current;
        const params = new URLSearchParams();

        if (currentTab === 'archive') {
            params.set('arsip', '1');
        }

        if (currentSearch.trim()) {
            params.set('q', currentSearch.trim());
        }

        setLoading(true);

        try {
            const data = await sendJson<{
                data: Conversation[];
                unread_total: number;
            }>(`/chat/percakapan?${params.toString()}`);

            // Abaikan respons lama bila filter sudah berubah.
            if (
                query.current.tab === currentTab &&
                query.current.search === currentSearch
            ) {
                setConversations(data.data);
                emitChatUnread(data.unread_total);
            }
        } catch {
            // Daftar lama tetap ditampilkan; dicoba lagi pada pembaruan berikutnya.
        } finally {
            setLoading(false);
        }
    }, []);

    // Tab/pencarian berubah → muat ulang (pencarian di-debounce).
    useEffect(() => {
        const changed =
            query.current.tab !== tab || query.current.search !== search;
        query.current = { tab, search };

        if (!changed) {
            return;
        }

        const timer = window.setTimeout(() => void refresh(), 300);

        return () => window.clearTimeout(timer);
    }, [tab, search, refresh]);

    // Pesan baru (dari RealtimeBridge) → perbarui urutan & cuplikan daftar.
    useEffect(() => {
        const onMessage = () => void refresh();

        window.addEventListener(CHAT_MESSAGE_EVENT, onMessage);

        return () => window.removeEventListener(CHAT_MESSAGE_EVENT, onMessage);
    }, [refresh]);

    // Fallback tanpa realtime.
    useEffect(() => {
        if (status === 'connected') {
            return;
        }

        const timer = window.setInterval(() => {
            if (document.visibilityState === 'visible') {
                void refresh();
            }
        }, POLL_MS);

        return () => window.clearInterval(timer);
    }, [status, refresh]);

    // Toast pesan untuk percakapan yang sedang dibuka ditekan.
    useEffect(() => {
        setOpenConversation(active?.public_id ?? null);

        return () => setOpenConversation(null);
    }, [active?.public_id]);

    const onChanged = useCallback(
        (patch: Partial<Conversation> | null) => {
            if (patch === null) {
                void refresh();

                return;
            }

            setActive((current) =>
                current ? { ...current, ...patch } : current,
            );

            const id = active?.public_id;

            setConversations((current) =>
                current.map((c) =>
                    c.public_id === id ? { ...c, ...patch } : c,
                ),
            );

            if (patch.archived !== undefined || patch.muted !== undefined) {
                void refresh();
            }
        },
        [active?.public_id, refresh],
    );

    const Layout = auth.activeRole === 'guardian' ? ParentShell : StaffShell;

    return (
        <Layout base={base}>
            <Head
                title={active?.other ? `Chat · ${active.other.name}` : 'Chat'}
            />
            <div className="flex h-full min-h-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <aside
                    className={cn(
                        'min-h-0 w-full flex-col border-slate-200 md:flex md:w-80 md:border-r lg:w-96',
                        active ? 'hidden' : 'flex',
                    )}
                    aria-label="Daftar percakapan"
                >
                    <ConversationList
                        base={base}
                        conversations={conversations}
                        activeId={active?.public_id ?? null}
                        tab={tab}
                        onTabChange={setTab}
                        search={search}
                        onSearchChange={setSearch}
                        loading={loading}
                        online={online}
                        onNew={() => setNewOpen(true)}
                    />
                </aside>
                <section
                    className={cn(
                        'min-h-0 min-w-0 flex-1 flex-col',
                        active ? 'flex' : 'hidden md:flex',
                    )}
                    aria-label="Ruang chat"
                >
                    {active ? (
                        <ChatRoom
                            key={active.public_id}
                            base={base}
                            conversation={active}
                            me={me}
                            online={
                                active.other !== null &&
                                online.has(active.other.public_id)
                            }
                            onChanged={onChanged}
                        />
                    ) : (
                        <EmptyState
                            icon={MessagesSquare}
                            title="Pilih percakapan"
                            description="Pilih percakapan di sebelah kiri atau mulai percakapan baru."
                            className="flex-1"
                        />
                    )}
                </section>
            </div>
            <NewConversationDialog
                base={base}
                open={newOpen}
                onOpenChange={setNewOpen}
            />
        </Layout>
    );
}

function StaffShell({ base, children }: { base: string; children: ReactNode }) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Chat', href: base }]}>
            {/* Tinggi layar dikurangi topbar (4rem) + padding main (3rem). */}
            <div className="h-[calc(100dvh-7rem)] min-h-[28rem]">
                {children}
            </div>
        </AppLayout>
    );
}

function ParentShell({ children }: { base: string; children: ReactNode }) {
    return (
        <ParentLayout>
            {/* Ponsel: header 3.5rem + padding 2.5rem + bottom nav 4.5rem; desktop: header + padding 4rem. */}
            <div className="h-[calc(100dvh-10.5rem-env(safe-area-inset-bottom))] min-h-[26rem] md:h-[calc(100dvh-7.5rem)]">
                {children}
            </div>
        </ParentLayout>
    );
}
