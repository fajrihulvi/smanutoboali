import {
    AlertCircle,
    Check,
    CheckCheck,
    Clock,
    FileText,
    MoreVertical,
    Pencil,
    RotateCw,
    Trash2,
    X,
} from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';
import { timeLabel } from '@/Modules/Chat/lib';
import type { UiMessage } from '@/Modules/Chat/types';

type Props = {
    message: UiMessage;
    onOpenImage: (url: string, name: string) => void;
    onImageLoad: () => void;
    onRetry: (message: UiMessage) => void;
    onDiscard: (message: UiMessage) => void;
    onEdit: (message: UiMessage) => void;
    onDelete: (message: UiMessage) => void;
};

/**
 * Bubble pesan: milik sendiri brand-600 teks putih di kanan, lawan bicara putih berborder di kiri.
 */
export function MessageBubble({
    message,
    onOpenImage,
    onImageLoad,
    onRetry,
    onDiscard,
    onEdit,
    onDelete,
}: Props) {
    const mine = message.is_mine === true;
    const attachment = message.attachment;
    const canEdit = !message.status && message.can?.edit;
    const canDelete = !message.status && message.can?.delete;

    return (
        <div
            className={cn(
                'group flex items-end gap-1',
                mine ? 'justify-end' : 'justify-start',
            )}
        >
            {mine && (canEdit || canDelete) && (
                <DropdownMenu>
                    <DropdownMenuTrigger
                        className="mb-1 rounded-full p-1 text-slate-400 opacity-100 transition-opacity duration-150 hover:bg-slate-100 hover:text-slate-600 focus-visible:opacity-100 md:opacity-0 md:group-hover:opacity-100"
                        aria-label="Aksi pesan"
                    >
                        <MoreVertical className="size-4" />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        {canEdit && (
                            <DropdownMenuItem onSelect={() => onEdit(message)}>
                                <Pencil />
                                Ubah
                            </DropdownMenuItem>
                        )}
                        {canDelete && (
                            <DropdownMenuItem
                                variant="destructive"
                                onSelect={() => onDelete(message)}
                            >
                                <Trash2 />
                                Hapus
                            </DropdownMenuItem>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            )}

            <div
                className={cn(
                    'max-w-[85%] rounded-2xl px-3 py-2 text-sm shadow-sm sm:max-w-[70%]',
                    mine
                        ? 'rounded-br-md bg-brand-600 text-white'
                        : 'rounded-bl-md border border-slate-200 bg-white text-slate-800',
                    message.status === 'sending' && 'opacity-70',
                    message.status === 'failed' && 'bg-rose-600',
                )}
            >
                {message.deleted ? (
                    <p
                        className={cn(
                            'italic',
                            mine ? 'text-white/70' : 'text-slate-400',
                        )}
                    >
                        Pesan dihapus
                    </p>
                ) : (
                    <>
                        {attachment?.is_image && (
                            <button
                                type="button"
                                onClick={() =>
                                    onOpenImage(attachment.url, attachment.name)
                                }
                                className="-mx-1 mb-1 block overflow-hidden rounded-xl"
                                aria-label={`Lihat ${attachment.name}`}
                            >
                                <img
                                    src={attachment.url}
                                    alt={attachment.name}
                                    onLoad={onImageLoad}
                                    className="max-h-72 w-full max-w-xs object-cover"
                                    style={
                                        attachment.width && attachment.height
                                            ? {
                                                  aspectRatio: `${attachment.width} / ${attachment.height}`,
                                              }
                                            : undefined
                                    }
                                />
                            </button>
                        )}
                        {attachment && !attachment.is_image && (
                            <a
                                href={attachment.url}
                                target="_blank"
                                rel="noreferrer"
                                className={cn(
                                    '-mx-1 mb-1 flex items-center gap-2 rounded-xl p-2',
                                    mine
                                        ? 'bg-white/15 hover:bg-white/20'
                                        : 'bg-slate-50 hover:bg-slate-100',
                                )}
                            >
                                <FileText
                                    className={cn(
                                        'size-8 shrink-0',
                                        mine ? 'text-white' : 'text-brand-500',
                                    )}
                                />
                                <span className="min-w-0">
                                    <span className="block truncate font-medium">
                                        {attachment.name}
                                    </span>
                                    <span
                                        className={cn(
                                            'block text-xs',
                                            mine
                                                ? 'text-white/70'
                                                : 'text-slate-500',
                                        )}
                                    >
                                        PDF · {formatFileSize(attachment.size)}
                                    </span>
                                </span>
                            </a>
                        )}
                        {message.body && (
                            <p className="break-words whitespace-pre-wrap">
                                {message.body}
                            </p>
                        )}
                    </>
                )}

                <div
                    className={cn(
                        'mt-0.5 flex items-center justify-end gap-1 text-[11px]',
                        mine ? 'text-white/70' : 'text-slate-400',
                    )}
                >
                    {message.edited_at && !message.deleted && (
                        <span>diubah ·</span>
                    )}
                    <time dateTime={message.created_at}>
                        {timeLabel(message.created_at)}
                    </time>
                    {mine && <DeliveryStatus message={message} />}
                </div>

                {message.status === 'failed' && (
                    <div className="mt-1 flex flex-wrap items-center gap-2 border-t border-white/20 pt-1 text-xs">
                        <span className="flex items-center gap-1">
                            <AlertCircle className="size-3.5" />
                            {message.error ?? 'Gagal terkirim'}
                        </span>
                        <button
                            type="button"
                            onClick={() => onRetry(message)}
                            className="flex items-center gap-1 font-semibold underline-offset-2 hover:underline"
                        >
                            <RotateCw className="size-3.5" />
                            Coba lagi
                        </button>
                        <button
                            type="button"
                            onClick={() => onDiscard(message)}
                            className="flex items-center gap-1 underline-offset-2 hover:underline"
                        >
                            <X className="size-3.5" />
                            Buang
                        </button>
                    </div>
                )}
            </div>
        </div>
    );
}

function DeliveryStatus({ message }: { message: UiMessage }) {
    if (message.status === 'sending') {
        return <Clock className="size-3.5" aria-label="Mengirim" />;
    }

    if (message.status === 'failed') {
        return null;
    }

    return message.read ? (
        <CheckCheck className="size-3.5 text-sky-200" aria-label="Dibaca" />
    ) : (
        <Check className="size-3.5" aria-label="Terkirim" />
    );
}
