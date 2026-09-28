import { Link } from '@inertiajs/react';
import { BellOff, MessageSquarePlus, Search } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';
import { cn } from '@/lib/utils';
import { ChatAvatar } from '@/Modules/Chat/Components/ChatAvatar';
import { listTimeLabel } from '@/Modules/Chat/lib';
import type { Conversation } from '@/Modules/Chat/types';

export type ListTab = 'inbox' | 'archive';

type Props = {
    base: string;
    conversations: Conversation[];
    activeId: string | null;
    tab: ListTab;
    onTabChange: (tab: ListTab) => void;
    search: string;
    onSearchChange: (search: string) => void;
    loading: boolean;
    online: Set<string>;
    onNew: () => void;
};

const TABS: { value: ListTab; label: string }[] = [
    { value: 'inbox', label: 'Kotak masuk' },
    { value: 'archive', label: 'Arsip' },
];

/**
 * Panel daftar percakapan: pencarian, tab kotak masuk/arsip, dan baris percakapan.
 */
export function ConversationList({
    base,
    conversations,
    activeId,
    tab,
    onTabChange,
    search,
    onSearchChange,
    loading,
    online,
    onNew,
}: Props) {
    return (
        <div className="flex min-h-0 flex-1 flex-col">
            <div className="space-y-3 border-b border-slate-200 p-4">
                <div className="flex items-center justify-between gap-2">
                    <h1 className="text-lg font-semibold text-slate-900">
                        Chat
                    </h1>
                    <Button size="sm" onClick={onNew}>
                        <MessageSquarePlus />
                        Percakapan baru
                    </Button>
                </div>
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        type="search"
                        value={search}
                        onChange={(event) => onSearchChange(event.target.value)}
                        placeholder="Cari nama atau anak…"
                        className="pl-9"
                        aria-label="Cari percakapan"
                    />
                </div>
                <div
                    className="grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1"
                    role="tablist"
                >
                    {TABS.map((item) => (
                        <button
                            key={item.value}
                            type="button"
                            role="tab"
                            aria-selected={tab === item.value}
                            onClick={() => onTabChange(item.value)}
                            className={cn(
                                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors duration-150',
                                tab === item.value
                                    ? 'bg-white text-slate-900 shadow-sm'
                                    : 'text-slate-500 hover:text-slate-700',
                            )}
                        >
                            {item.label}
                        </button>
                    ))}
                </div>
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto">
                {loading && conversations.length === 0 ? (
                    <ul className="space-y-1 p-2" aria-busy="true">
                        {[0, 1, 2, 3].map((i) => (
                            <li key={i} className="flex gap-3 p-2">
                                <Skeleton className="size-10 rounded-full" />
                                <div className="flex-1 space-y-2">
                                    <Skeleton className="h-4 w-2/3" />
                                    <Skeleton className="h-3 w-full" />
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : conversations.length === 0 ? (
                    <EmptyState
                        title={
                            search
                                ? 'Tidak ditemukan'
                                : tab === 'archive'
                                  ? 'Arsip kosong'
                                  : 'Belum ada percakapan'
                        }
                        description={
                            search
                                ? 'Coba kata kunci lain.'
                                : tab === 'archive'
                                  ? 'Percakapan yang diarsipkan tampil di sini.'
                                  : 'Mulai percakapan baru dengan guru, orang tua, atau staf sekolah.'
                        }
                    />
                ) : (
                    <ul className="divide-y divide-slate-100">
                        {conversations.map((conversation) => (
                            <li key={conversation.public_id}>
                                <ConversationRow
                                    base={base}
                                    conversation={conversation}
                                    active={conversation.public_id === activeId}
                                    online={
                                        conversation.other !== null &&
                                        online.has(conversation.other.public_id)
                                    }
                                />
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
}

function ConversationRow({
    base,
    conversation,
    active,
    online,
}: {
    base: string;
    conversation: Conversation;
    active: boolean;
    online: boolean;
}) {
    const name = conversation.other?.name ?? 'Pengguna dihapus';
    const unread = conversation.unread > 0;

    return (
        <Link
            href={`${base}/${conversation.public_id}`}
            only={['active']}
            preserveState
            preserveScroll
            className={cn(
                'flex gap-3 px-4 py-3 transition-colors duration-150',
                active ? 'bg-brand-50' : 'hover:bg-slate-50',
            )}
            aria-current={active ? 'page' : undefined}
        >
            <ChatAvatar name={name} online={online} />
            <div className="min-w-0 flex-1">
                <div className="flex items-baseline gap-2">
                    <p
                        className={cn(
                            'min-w-0 flex-1 truncate text-sm text-slate-900',
                            unread ? 'font-semibold' : 'font-medium',
                        )}
                    >
                        {name}
                    </p>
                    {conversation.last_message && (
                        <time
                            dateTime={conversation.last_message.created_at}
                            className={cn(
                                'shrink-0 text-xs',
                                unread ? 'text-brand-600' : 'text-slate-400',
                            )}
                        >
                            {listTimeLabel(
                                conversation.last_message.created_at,
                            )}
                        </time>
                    )}
                </div>
                <p className="truncate text-xs text-slate-500">
                    {conversation.other?.role}
                    {conversation.student &&
                        !conversation.other?.role.includes(
                            conversation.student.name,
                        ) &&
                        ` · ${conversation.student.name}`}
                </p>
                <div className="mt-0.5 flex items-center gap-2">
                    <p
                        className={cn(
                            'min-w-0 flex-1 truncate text-sm',
                            unread
                                ? 'font-medium text-slate-800'
                                : 'text-slate-500',
                        )}
                    >
                        {conversation.last_message?.is_mine && 'Anda: '}
                        {conversation.last_message?.preview ??
                            'Belum ada pesan'}
                    </p>
                    {conversation.muted && (
                        <BellOff
                            className="size-3.5 shrink-0 text-slate-400"
                            aria-label="Dibisukan"
                        />
                    )}
                    {unread && (
                        <span
                            className={cn(
                                'min-w-5 shrink-0 rounded-full px-1.5 text-center text-xs leading-5 font-semibold text-white',
                                conversation.muted
                                    ? 'bg-slate-400'
                                    : 'bg-brand-600',
                            )}
                            aria-label={`${conversation.unread} pesan belum dibaca`}
                        >
                            {conversation.unread > 99
                                ? '99+'
                                : conversation.unread}
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}
