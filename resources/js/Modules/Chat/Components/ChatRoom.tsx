import { Link } from '@inertiajs/react';
import {
    Archive,
    ArchiveRestore,
    ArrowLeft,
    Bell,
    BellOff,
    Lock,
    MoreVertical,
} from 'lucide-react';
import {
    Fragment,
    useCallback,
    useEffect,
    useLayoutEffect,
    useRef,
    useState,
} from 'react';
import { toast } from 'sonner';
import { ConfirmDialog } from '@/Components/shared/ConfirmDialog';
import { Lightbox } from '@/Components/shared/Lightbox';
import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';
import { useEchoChannel } from '@/hooks/use-echo-channel';
import { useRealtimeStatus } from '@/hooks/use-realtime-status';
import { emitChatUnread } from '@/lib/chat-events';
import { HttpError, sendJson } from '@/lib/http';
import { ChatAvatar } from '@/Modules/Chat/Components/ChatAvatar';
import { Composer } from '@/Modules/Chat/Components/Composer';
import { MessageBubble } from '@/Modules/Chat/Components/MessageBubble';
import {
    dayLabel,
    markReadUpTo,
    mergeMessages,
    startsNewDay,
} from '@/Modules/Chat/lib';
import type {
    ChatMessage,
    Conversation,
    MessagePage,
    ReadEvent,
    SentEvent,
    UiMessage,
} from '@/Modules/Chat/types';

type Props = {
    base: string;
    conversation: Conversation;
    me: string;
    online: boolean;
    /** Percakapan berubah (arsip/bisu, pesan baru terkirim, sudah dibaca). */
    onChanged: (patch: Partial<Conversation> | null) => void;
};

/** Jarak dari bawah (px) yang masih dianggap "sedang di bawah". */
const STICKY_PX = 120;
/** Fallback tanpa realtime: muat ulang tiap 15 detik saat tab terlihat. */
const POLL_MS = 15_000;

const errorText = (error: unknown) =>
    error instanceof HttpError
        ? error.message
        : 'Gagal mengirim. Periksa koneksi Anda.';

/**
 * Ruang chat: riwayat (infinite scroll ke atas), realtime, optimistic send, ubah/hapus.
 * Dipasang dengan `key` = public_id percakapan sehingga state direset per percakapan.
 */
export function ChatRoom({ base, conversation, me, online, onChanged }: Props) {
    const api = `/chat/percakapan/${conversation.public_id}`;
    const [messages, setMessages] = useState<UiMessage[]>([]);
    const [cursor, setCursor] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [loadingOlder, setLoadingOlder] = useState(false);
    const [lightbox, setLightbox] = useState<{
        url: string;
        name: string;
    } | null>(null);
    const [editing, setEditing] = useState<{
        message: UiMessage;
        body: string;
    } | null>(null);
    const [deleting, setDeleting] = useState<UiMessage | null>(null);
    const [busy, setBusy] = useState(false);
    const scroller = useRef<HTMLDivElement>(null);
    const stickToBottom = useRef(true);
    const restoreFrom = useRef<number | null>(null);
    const status = useRealtimeStatus();
    const onChangedRef = useRef(onChanged);
    const messagesRef = useRef(messages);

    useEffect(() => {
        onChangedRef.current = onChanged;
        messagesRef.current = messages;
    });

    const withViewer = useCallback(
        (message: ChatMessage): UiMessage => {
            const mine = message.sender?.public_id === me;

            return {
                ...message,
                is_mine: message.is_mine ?? mine,
                can: message.deleted
                    ? { edit: false, delete: false }
                    : (message.can ?? {
                          edit: mine && message.type === 'TEXT',
                          delete: mine,
                      }),
            };
        },
        [me],
    );

    const markRead = useCallback(() => {
        if (document.visibilityState !== 'visible') {
            return;
        }

        sendJson<{ unread_total: number }>(`${api}/baca`, 'POST')
            .then(({ unread_total }) => {
                emitChatUnread(unread_total);
                onChangedRef.current({ unread: 0 });
            })
            .catch(() => undefined);
    }, [api]);

    // Halaman pertama (terbaru) saat percakapan dibuka.
    useEffect(() => {
        let cancelled = false;

        sendJson<MessagePage>(`${api}/pesan`)
            .then((page) => {
                if (cancelled) {
                    return;
                }

                setMessages((current) =>
                    mergeMessages(current, page.data.map(withViewer)),
                );
                setCursor(page.next_cursor);
                markRead();
            })
            .catch(
                (error: unknown) => !cancelled && toast.error(errorText(error)),
            )
            .finally(() => !cancelled && setLoading(false));

        return () => {
            cancelled = true;
        };
    }, [api, markRead, withViewer]);

    /** Fallback polling: gabungkan halaman terbaru tanpa menghapus pesan lama. */
    const loadLatest = useCallback(async () => {
        try {
            const page = await sendJson<MessagePage>(`${api}/pesan`);
            const fresh = page.data.map(withViewer);
            const known = new Set(messagesRef.current.map((m) => m.public_id));

            setMessages((current) => mergeMessages(current, fresh));

            if (fresh.some((m) => !m.is_mine && !known.has(m.public_id))) {
                markRead();
            }
        } catch {
            // Dicoba lagi pada siklus berikutnya.
        }
    }, [api, markRead, withViewer]);

    const loadOlder = useCallback(async () => {
        const el = scroller.current;

        if (!cursor || loadingOlder || !el) {
            return;
        }

        setLoadingOlder(true);
        restoreFrom.current = el.scrollHeight - el.scrollTop;

        try {
            const page = await sendJson<MessagePage>(
                `${api}/pesan?cursor=${encodeURIComponent(cursor)}`,
            );
            setMessages((current) =>
                mergeMessages(current, page.data.map(withViewer)),
            );
            setCursor(page.next_cursor);
        } catch (error) {
            restoreFrom.current = null;
            toast.error(errorText(error));
        } finally {
            setLoadingOlder(false);
        }
    }, [api, cursor, loadingOlder, withViewer]);

    // Pertahankan posisi saat pesan lama dimuat; ikut ke bawah bila pengguna sedang di bawah.
    useLayoutEffect(() => {
        const el = scroller.current;

        if (!el) {
            return;
        }

        if (restoreFrom.current !== null) {
            el.scrollTop = el.scrollHeight - restoreFrom.current;
            restoreFrom.current = null;
        } else if (stickToBottom.current) {
            el.scrollTop = el.scrollHeight;
        }
    }, [messages]);

    const onScroll = () => {
        const el = scroller.current;

        if (!el) {
            return;
        }

        stickToBottom.current =
            el.scrollHeight - el.scrollTop - el.clientHeight < STICKY_PX;

        if (el.scrollTop < 80) {
            void loadOlder();
        }
    };

    const scrollToBottomIfSticky = () => {
        const el = scroller.current;

        if (el && stickToBottom.current) {
            el.scrollTop = el.scrollHeight;
        }
    };

    useEchoChannel(`conversation.${conversation.public_id}`, {
        '.message.sent': ({ message }: SentEvent) => {
            const incoming = withViewer(message);

            setMessages((current) =>
                // Pesan sendiri yang masih "mengirim" akan diganti respons POST.
                incoming.is_mine &&
                current.some((m) => m.status === 'sending') &&
                !current.some((m) => m.public_id === incoming.public_id)
                    ? current
                    : mergeMessages(current, [incoming]),
            );

            if (!incoming.is_mine) {
                markRead();
            }
        },
        '.message.updated': ({ message }: SentEvent) =>
            setMessages((current) =>
                mergeMessages(current, [withViewer(message)]),
            ),
        '.conversation.read': (event: ReadEvent) => {
            if (event.reader !== me) {
                setMessages((current) =>
                    markReadUpTo(current, event.last_read_message),
                );
            }
        },
    });

    // Fallback tanpa realtime: polling ringan + muat ulang saat tab kembali aktif.
    useEffect(() => {
        const onVisible = () => {
            if (document.visibilityState === 'visible') {
                void loadLatest();
            }
        };

        document.addEventListener('visibilitychange', onVisible);

        const timer =
            status === 'connected'
                ? null
                : window.setInterval(() => {
                      if (document.visibilityState === 'visible') {
                          void loadLatest();
                      }
                  }, POLL_MS);

        return () => {
            document.removeEventListener('visibilitychange', onVisible);

            if (timer !== null) {
                window.clearInterval(timer);
            }
        };
    }, [status, loadLatest]);

    const deliver = (optimistic: UiMessage) => {
        const form = new FormData();

        if (optimistic.body) {
            form.append('body', optimistic.body);
        }

        if (optimistic.file) {
            form.append('file', optimistic.file);
        }

        sendJson<{ data: ChatMessage }>(`${api}/pesan`, 'POST', form)
            .then(({ data }) => {
                if (optimistic.attachment?.url.startsWith('blob:')) {
                    URL.revokeObjectURL(optimistic.attachment.url);
                }

                setMessages((current) =>
                    mergeMessages(
                        current.filter(
                            (m) => m.client_id !== optimistic.client_id,
                        ),
                        [withViewer(data)],
                    ),
                );
                onChangedRef.current(null);
            })
            .catch((error: unknown) =>
                setMessages((current) =>
                    current.map((m) =>
                        m.client_id === optimistic.client_id
                            ? {
                                  ...m,
                                  status: 'failed',
                                  error: errorText(error),
                              }
                            : m,
                    ),
                ),
            );
    };

    const send = (body: string, file: File | null) => {
        const clientId = `tmp-${Date.now()}-${Math.random().toString(36).slice(2)}`;
        const isImage = file?.type.startsWith('image/') ?? false;
        const optimistic: UiMessage = {
            public_id: clientId,
            client_id: clientId,
            type: file ? (isImage ? 'IMAGE' : 'FILE') : 'TEXT',
            body: body || null,
            deleted: false,
            sender: { public_id: me, name: '' },
            attachment: file
                ? {
                      public_id: clientId,
                      name: file.name,
                      mime_type: file.type,
                      is_image: isImage,
                      size: file.size,
                      width: null,
                      height: null,
                      url: isImage ? URL.createObjectURL(file) : '#',
                  }
                : null,
            created_at: new Date().toISOString(),
            edited_at: null,
            is_mine: true,
            read: false,
            status: 'sending',
            file,
        };

        stickToBottom.current = true;
        setMessages((current) => [...current, optimistic]);
        deliver(optimistic);
    };

    const retry = (message: UiMessage) => {
        const again: UiMessage = {
            ...message,
            status: 'sending',
            error: undefined,
        };
        setMessages((current) =>
            current.map((m) => (m.client_id === message.client_id ? again : m)),
        );
        deliver(again);
    };

    const discard = (message: UiMessage) =>
        setMessages((current) =>
            current.filter((m) => m.client_id !== message.client_id),
        );

    const saveEdit = () => {
        if (!editing || editing.body.trim() === '') {
            return;
        }

        setBusy(true);
        sendJson<{ data: ChatMessage }>(
            `/chat/pesan/${editing.message.public_id}`,
            'PATCH',
            {
                body: editing.body,
            },
        )
            .then(({ data }) => {
                setMessages((current) =>
                    mergeMessages(current, [withViewer(data)]),
                );
                setEditing(null);
            })
            .catch((error: unknown) => toast.error(errorText(error)))
            .finally(() => setBusy(false));
    };

    const confirmDelete = () => {
        if (!deleting) {
            return;
        }

        setBusy(true);
        sendJson<{ data: ChatMessage }>(
            `/chat/pesan/${deleting.public_id}`,
            'DELETE',
        )
            .then(({ data }) => {
                setMessages((current) =>
                    mergeMessages(current, [withViewer(data)]),
                );
                setDeleting(null);
                onChangedRef.current(null);
            })
            .catch((error: unknown) => toast.error(errorText(error)))
            .finally(() => setBusy(false));
    };

    const updatePreferences = (patch: {
        archived?: boolean;
        muted?: boolean;
    }) => {
        sendJson<{ data: Conversation }>(api, 'PATCH', patch)
            .then(({ data }) => {
                onChangedRef.current(data);
                toast.success(
                    patch.archived !== undefined
                        ? patch.archived
                            ? 'Percakapan diarsipkan.'
                            : 'Percakapan dikeluarkan dari arsip.'
                        : patch.muted
                          ? 'Notifikasi percakapan dibisukan.'
                          : 'Notifikasi percakapan diaktifkan.',
                );
            })
            .catch((error: unknown) => toast.error(errorText(error)));
    };

    const name = conversation.other?.name ?? 'Pengguna dihapus';
    const canSend = conversation.can_send !== false;

    return (
        <div className="flex min-h-0 flex-1 flex-col">
            <header className="flex items-center gap-3 border-b border-slate-200 bg-white px-3 py-2.5 sm:px-4">
                <Link
                    href={base}
                    only={['active']}
                    preserveState
                    className="-ml-1 rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden"
                    aria-label="Kembali ke daftar percakapan"
                >
                    <ArrowLeft className="size-5" />
                </Link>
                <ChatAvatar name={name} online={online} />
                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-semibold text-slate-900">
                        {name}
                    </p>
                    <p className="truncate text-xs text-slate-500">
                        {online ? 'Online · ' : ''}
                        {conversation.other?.role}
                        {conversation.student &&
                            !conversation.other?.role.includes(
                                conversation.student.name,
                            ) &&
                            ` · ${conversation.student.name}`}
                    </p>
                </div>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Opsi percakapan"
                        >
                            <MoreVertical />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            onSelect={() =>
                                updatePreferences({
                                    muted: !conversation.muted,
                                })
                            }
                        >
                            {conversation.muted ? <Bell /> : <BellOff />}
                            {conversation.muted
                                ? 'Aktifkan notifikasi'
                                : 'Bisukan notifikasi'}
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                updatePreferences({
                                    archived: !conversation.archived,
                                })
                            }
                        >
                            {conversation.archived ? (
                                <ArchiveRestore />
                            ) : (
                                <Archive />
                            )}
                            {conversation.archived
                                ? 'Keluarkan dari arsip'
                                : 'Arsipkan'}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </header>

            <div
                ref={scroller}
                onScroll={onScroll}
                className="min-h-0 flex-1 space-y-2 overflow-y-auto bg-slate-50 px-3 py-4 sm:px-6"
                aria-live="polite"
                aria-busy={loading}
            >
                {loadingOlder && (
                    <div className="flex justify-center py-2">
                        <Spinner className="text-slate-400" />
                    </div>
                )}
                {!loading && !cursor && messages.length > 0 && (
                    <p className="py-2 text-center text-xs text-slate-400">
                        Awal percakapan
                    </p>
                )}
                {loading ? (
                    <div className="flex h-full items-center justify-center">
                        <Spinner className="size-6 text-slate-400" />
                    </div>
                ) : messages.length === 0 ? (
                    <div className="flex h-full flex-col items-center justify-center text-center text-sm text-slate-500">
                        <p className="font-medium text-slate-700">
                            Belum ada pesan
                        </p>
                        <p>Sapa {name} untuk memulai percakapan.</p>
                    </div>
                ) : (
                    messages.map((message, index) => (
                        <Fragment key={message.client_id ?? message.public_id}>
                            {startsNewDay(messages, index) && (
                                <div className="sticky top-0 z-10 flex justify-center py-1">
                                    <span className="rounded-full bg-white/95 px-3 py-0.5 text-xs font-medium text-slate-500 shadow-sm ring-1 ring-slate-200">
                                        {dayLabel(message.created_at)}
                                    </span>
                                </div>
                            )}
                            <MessageBubble
                                message={message}
                                onOpenImage={(url, imageName) =>
                                    setLightbox({ url, name: imageName })
                                }
                                onImageLoad={scrollToBottomIfSticky}
                                onRetry={retry}
                                onDiscard={discard}
                                onEdit={(m) =>
                                    setEditing({
                                        message: m,
                                        body: m.body ?? '',
                                    })
                                }
                                onDelete={setDeleting}
                            />
                        </Fragment>
                    ))
                )}
            </div>

            {canSend ? (
                <Composer onSend={send} />
            ) : (
                <p className="flex items-center justify-center gap-2 border-t border-slate-200 bg-white p-4 text-center text-sm text-slate-500">
                    <Lock className="size-4 shrink-0" />
                    Percakapan ini hanya bisa dibaca karena Anda tidak lagi
                    terhubung dengan pengguna ini.
                </p>
            )}

            <Lightbox
                images={lightbox ? [lightbox] : []}
                index={lightbox ? 0 : null}
                onIndexChange={(index) => index === null && setLightbox(null)}
                title={name}
            />

            <Dialog
                open={editing !== null}
                onOpenChange={(open) => !open && setEditing(null)}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Ubah pesan</DialogTitle>
                        <DialogDescription>
                            Pesan hanya bisa diubah dalam 15 menit setelah
                            dikirim.
                        </DialogDescription>
                    </DialogHeader>
                    <Textarea
                        rows={4}
                        maxLength={4000}
                        value={editing?.body ?? ''}
                        onChange={(event) =>
                            setEditing((current) =>
                                current
                                    ? { ...current, body: event.target.value }
                                    : current,
                            )
                        }
                        aria-label="Isi pesan"
                    />
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setEditing(null)}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={saveEdit}
                            disabled={busy || !editing?.body.trim()}
                        >
                            {busy && <Spinner />}
                            Simpan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => !open && setDeleting(null)}
                title="Hapus pesan ini?"
                description='Pesan akan tampil sebagai "Pesan dihapus" untuk kedua pihak, dan lampirannya ikut dihapus.'
                confirmLabel="Hapus"
                variant="danger"
                processing={busy}
                onConfirm={confirmDelete}
            />
        </div>
    );
}
