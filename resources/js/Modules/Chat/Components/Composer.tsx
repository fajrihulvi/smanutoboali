import { FileText, Paperclip, SendHorizontal, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import { toast } from 'sonner';
import { Button } from '@/Components/ui/button';
import { formatFileSize } from '@/lib/format';
import {
    ATTACHMENT_TYPES,
    attachmentError,
    MAX_ATTACHMENT_BYTES,
} from '@/Modules/Chat/lib';

type Props = {
    onSend: (body: string, file: File | null) => void;
    disabled?: boolean;
};

const MAX_HEIGHT = 160;

/**
 * Komposer: textarea auto-resize, Enter kirim, Shift+Enter baris baru, satu lampiran.
 */
export function Composer({ onSend, disabled = false }: Props) {
    const [body, setBody] = useState('');
    const [file, setFile] = useState<File | null>(null);
    const textarea = useRef<HTMLTextAreaElement>(null);
    const fileInput = useRef<HTMLInputElement>(null);

    // Tinggi mengikuti isi sampai batas, lalu scroll.
    useEffect(() => {
        const el = textarea.current;

        if (el) {
            el.style.height = 'auto';
            el.style.height = `${Math.min(el.scrollHeight, MAX_HEIGHT)}px`;
        }
    }, [body]);

    const preview = useMemo(
        () =>
            file && file.type.startsWith('image/')
                ? URL.createObjectURL(file)
                : null,
        [file],
    );

    useEffect(
        () => () => {
            if (preview) {
                URL.revokeObjectURL(preview);
            }
        },
        [preview],
    );

    const canSend = !disabled && (body.trim() !== '' || file !== null);

    const submit = () => {
        if (!canSend) {
            return;
        }

        onSend(body.trim(), file);
        setBody('');
        setFile(null);
        textarea.current?.focus();
    };

    const onKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
        if (
            event.key === 'Enter' &&
            !event.shiftKey &&
            !event.nativeEvent.isComposing
        ) {
            event.preventDefault();
            submit();
        }
    };

    const pick = (picked: File | undefined) => {
        if (!picked) {
            return;
        }

        const error = attachmentError(picked);

        if (error) {
            toast.error(error);
        } else {
            setFile(picked);
        }

        if (fileInput.current) {
            fileInput.current.value = '';
        }
    };

    return (
        <div className="border-t border-slate-200 bg-white p-3">
            {file && (
                <div className="mb-2 flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2 text-sm">
                    {preview ? (
                        <img
                            src={preview}
                            alt=""
                            className="size-10 rounded-lg object-cover"
                        />
                    ) : (
                        <FileText className="size-8 text-brand-500" />
                    )}
                    <span className="min-w-0 flex-1">
                        <span className="block truncate font-medium text-slate-800">
                            {file.name}
                        </span>
                        <span className="block text-xs text-slate-500">
                            {formatFileSize(file.size)}
                        </span>
                    </span>
                    <button
                        type="button"
                        onClick={() => setFile(null)}
                        className="rounded-full p-1 text-slate-500 hover:bg-slate-200"
                        aria-label="Hapus lampiran"
                    >
                        <X className="size-4" />
                    </button>
                </div>
            )}
            <div className="flex items-end gap-2">
                <input
                    ref={fileInput}
                    type="file"
                    className="hidden"
                    accept={ATTACHMENT_TYPES.join(',')}
                    onChange={(event) => pick(event.target.files?.[0])}
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="shrink-0 text-slate-500"
                    onClick={() => fileInput.current?.click()}
                    disabled={disabled}
                    aria-label={`Lampirkan gambar atau PDF (maks ${formatFileSize(MAX_ATTACHMENT_BYTES)})`}
                >
                    <Paperclip />
                </Button>
                <textarea
                    ref={textarea}
                    rows={1}
                    value={body}
                    maxLength={4000}
                    onChange={(event) => setBody(event.target.value)}
                    onKeyDown={onKeyDown}
                    disabled={disabled}
                    placeholder="Tulis pesan…"
                    aria-label="Tulis pesan"
                    className="max-h-40 min-h-10 min-w-0 flex-1 resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-400 focus:bg-white focus:ring-2 focus:ring-brand-100 disabled:opacity-60"
                />
                <Button
                    type="button"
                    size="icon"
                    className="shrink-0 rounded-full"
                    onClick={submit}
                    disabled={!canSend}
                    aria-label="Kirim"
                >
                    <SendHorizontal />
                </Button>
            </div>
            <p className="mt-1 hidden text-[11px] text-slate-400 sm:block">
                Enter untuk kirim · Shift+Enter untuk baris baru
            </p>
        </div>
    );
}
