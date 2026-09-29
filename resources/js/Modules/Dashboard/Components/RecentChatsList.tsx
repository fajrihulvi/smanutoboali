import { Link } from '@inertiajs/react';
import { PersonAvatar } from '@/Components/shared/PersonAvatar';
import { formatRelative } from '@/lib/format';
import { PanelEmpty } from '@/Modules/Dashboard/Components/DashboardPanel';
import type { ChatItem } from '@/Modules/Dashboard/types';

export function RecentChatsList({ items }: { items: ChatItem[] }) {
    if (items.length === 0) {
        return <PanelEmpty>Belum ada percakapan.</PanelEmpty>;
    }

    return (
        <ul className="-mx-2">
            {items.map((chat) => (
                <li key={chat.public_id}>
                    <Link
                        href={chat.url}
                        className="flex items-center gap-3 rounded-lg px-2 py-2 transition-colors duration-150 hover:bg-slate-50"
                    >
                        <PersonAvatar name={chat.name} className="size-9" />
                        <span className="min-w-0 flex-1">
                            <span className="flex items-baseline justify-between gap-2">
                                <span className="truncate text-sm font-medium text-slate-900">
                                    {chat.name}
                                </span>
                                <span className="shrink-0 text-xs text-slate-400">
                                    {formatRelative(chat.at)}
                                </span>
                            </span>
                            <span className="flex items-center gap-2">
                                <span className="min-w-0 flex-1 truncate text-xs text-slate-500">
                                    {chat.is_mine && 'Anda: '}
                                    {chat.preview}
                                    {chat.student && ` · ${chat.student}`}
                                </span>
                                {chat.unread > 0 && (
                                    <span className="rounded-full bg-brand-600 px-1.5 text-[11px] font-semibold text-white tabular-nums">
                                        {chat.unread}
                                    </span>
                                )}
                            </span>
                        </span>
                    </Link>
                </li>
            ))}
        </ul>
    );
}
