import { router } from '@inertiajs/react';
import { Search, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { EmptyState } from '@/Components/shared/EmptyState';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Skeleton } from '@/Components/ui/skeleton';
import { Spinner } from '@/Components/ui/spinner';
import { errorMessage, HttpError, sendJson } from '@/lib/http';
import { ChatAvatar } from '@/Modules/Chat/Components/ChatAvatar';
import type { Contact, Conversation, ContactGroup } from '@/Modules/Chat/types';

type Props = {
    base: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
};

/**
 * Pilih kontak yang boleh dihubungi (per anak untuk orang tua, per rombel untuk guru)
 * lalu buka percakapannya — percakapan lama dipakai ulang oleh server.
 */
export function NewConversationDialog({ base, open, onOpenChange }: Props) {
    const [search, setSearch] = useState('');
    const [groups, setGroups] = useState<ContactGroup[] | null>(null);
    const [starting, setStarting] = useState<string | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        let cancelled = false;
        const timer = window.setTimeout(() => {
            sendJson<{ groups: ContactGroup[] }>(
                `/chat/kontak?q=${encodeURIComponent(search)}`,
            )
                .then((data) => !cancelled && setGroups(data.groups))
                .catch((error: unknown) => {
                    if (!cancelled) {
                        setGroups([]);
                        toast.error(
                            error instanceof HttpError
                                ? error.message
                                : errorMessage(0, null),
                        );
                    }
                });
        }, 300);

        return () => {
            cancelled = true;
            window.clearTimeout(timer);
        };
    }, [open, search]);

    const start = (contact: Contact) => {
        const key = `${contact.user}:${contact.student?.public_id ?? ''}`;
        setStarting(key);

        sendJson<{ data: Conversation }>('/chat/percakapan', 'POST', {
            user: contact.user,
            student: contact.student?.public_id ?? null,
        })
            .then(({ data }) => {
                onOpenChange(false);
                router.visit(`${base}/${data.public_id}`, {
                    preserveState: true,
                });
            })
            .catch((error: unknown) =>
                toast.error(
                    error instanceof HttpError
                        ? error.message
                        : 'Gagal memulai percakapan.',
                ),
            )
            .finally(() => setStarting(null));
    };

    const empty =
        groups !== null && groups.every((group) => group.contacts.length === 0);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[85dvh] flex-col gap-4 sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Percakapan baru</DialogTitle>
                    <DialogDescription>
                        Hanya kontak yang terkait dengan Anda yang ditampilkan.
                    </DialogDescription>
                </DialogHeader>
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
                    <Input
                        type="search"
                        autoFocus
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Cari nama…"
                        className="pl-9"
                        aria-label="Cari kontak"
                    />
                </div>
                <div className="-mx-6 min-h-0 flex-1 overflow-y-auto px-6">
                    {groups === null ? (
                        <div className="space-y-3" aria-busy="true">
                            {[0, 1, 2].map((i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : (
                        <div className="space-y-5">
                            {groups.map((group) => (
                                <section key={group.key}>
                                    <h3 className="mb-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">
                                        {group.title}
                                    </h3>
                                    {group.contacts.length === 0 ? (
                                        <p className="py-2 text-sm text-slate-500">
                                            {group.hint ?? 'Tidak ada kontak.'}
                                        </p>
                                    ) : (
                                        <ul className="-mx-2">
                                            {group.contacts.map((contact) => {
                                                const key = `${contact.user}:${contact.student?.public_id ?? ''}`;

                                                return (
                                                    <li key={key}>
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                start(contact)
                                                            }
                                                            disabled={
                                                                starting !==
                                                                null
                                                            }
                                                            className="flex w-full items-center gap-3 rounded-xl px-2 py-2 text-left transition-colors duration-150 hover:bg-slate-50 disabled:opacity-60"
                                                        >
                                                            <ChatAvatar
                                                                name={
                                                                    contact.name
                                                                }
                                                                className="size-9"
                                                            />
                                                            <span className="min-w-0 flex-1">
                                                                <span className="block truncate text-sm font-medium text-slate-900">
                                                                    {
                                                                        contact.name
                                                                    }
                                                                </span>
                                                                <span className="block truncate text-xs text-slate-500">
                                                                    {contact.context ??
                                                                        contact.role}
                                                                </span>
                                                            </span>
                                                            {starting ===
                                                                key && (
                                                                <Spinner className="text-slate-400" />
                                                            )}
                                                        </button>
                                                    </li>
                                                );
                                            })}
                                        </ul>
                                    )}
                                </section>
                            ))}
                            {empty && !groups.some((g) => g.hint) && (
                                <EmptyState
                                    icon={Users}
                                    title="Tidak ada kontak"
                                    description={
                                        search
                                            ? 'Coba kata kunci lain.'
                                            : 'Belum ada guru, orang tua, atau staf yang terhubung dengan Anda.'
                                    }
                                />
                            )}
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
