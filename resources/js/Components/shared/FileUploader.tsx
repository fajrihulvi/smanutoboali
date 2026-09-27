import { FileText, UploadCloud, X } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState } from 'react';
import type { DragEvent } from 'react';
import { formatFileSize } from '@/lib/format';
import { cn } from '@/lib/utils';

type Props = {
    value: File[];
    onChange: (files: File[]) => void;
    /** Daftar MIME, contoh ['image/jpeg', 'image/png', 'application/pdf']. */
    accept?: string[];
    maxSizeKb?: number;
    multiple?: boolean;
    maxFiles?: number;
    hint?: string;
    /** Pesan error dari server (useForm errors). */
    error?: string;
    disabled?: boolean;
};

function Preview({ file, onRemove }: { file: File; onRemove: () => void }) {
    const isImage = file.type.startsWith('image/');
    const url = useMemo(
        () => (isImage ? URL.createObjectURL(file) : null),
        [file, isImage],
    );

    useEffect(
        () => () => {
            if (url) {
                URL.revokeObjectURL(url);
            }
        },
        [url],
    );

    return (
        <li className="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-2 pr-3">
            {url ? (
                <img
                    src={url}
                    alt={file.name}
                    className="size-12 rounded-lg object-cover"
                />
            ) : (
                <span className="flex size-12 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                    <FileText className="size-5" />
                </span>
            )}
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium text-slate-800">
                    {file.name}
                </p>
                <p className="text-xs text-slate-500">
                    {formatFileSize(file.size)}
                </p>
            </div>
            <button
                type="button"
                onClick={onRemove}
                className="rounded-md p-1 text-slate-400 transition-colors duration-150 hover:bg-rose-50 hover:text-rose-600"
                aria-label={`Hapus ${file.name}`}
            >
                <X className="size-4" />
            </button>
        </li>
    );
}

/**
 * Unggah file dengan drag & drop + preview. Validasi di sini hanya UX;
 * validasi MIME asli dan ukuran tetap dilakukan server (FileService).
 */
export function FileUploader({
    value,
    onChange,
    accept,
    maxSizeKb = 5120,
    multiple = false,
    maxFiles = 10,
    hint,
    error,
    disabled = false,
}: Props) {
    const inputId = useId();
    const inputRef = useRef<HTMLInputElement>(null);
    const [dragging, setDragging] = useState(false);
    const [localError, setLocalError] = useState<string | null>(null);

    const addFiles = (incoming: FileList | null) => {
        if (!incoming) {
            return;
        }

        const accepted: File[] = [];

        for (const file of Array.from(incoming)) {
            if (accept && !accept.includes(file.type)) {
                setLocalError(`Tipe file ${file.name} tidak diizinkan.`);
                continue;
            }

            if (file.size > maxSizeKb * 1024) {
                setLocalError(
                    `${file.name} melebihi ukuran maksimum ${formatFileSize(maxSizeKb * 1024)}.`,
                );
                continue;
            }

            accepted.push(file);
        }

        if (accepted.length === 0) {
            return;
        }

        setLocalError(null);
        onChange(
            multiple
                ? [...value, ...accepted].slice(0, maxFiles)
                : accepted.slice(0, 1),
        );
    };

    const onDrop = (event: DragEvent<HTMLLabelElement>) => {
        event.preventDefault();
        setDragging(false);

        if (!disabled) {
            addFiles(event.dataTransfer.files);
        }
    };

    const message = error ?? localError;

    return (
        <div className="space-y-3">
            <label
                htmlFor={inputId}
                onDragOver={(event) => {
                    event.preventDefault();
                    setDragging(true);
                }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                className={cn(
                    'flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-6 py-8 text-center transition-colors duration-150',
                    dragging
                        ? 'border-brand-500 bg-brand-50'
                        : 'border-slate-200 bg-slate-50 hover:border-brand-300 hover:bg-brand-50/50',
                    message && 'border-rose-300',
                    disabled && 'pointer-events-none opacity-50',
                )}
            >
                <span className="mb-3 flex size-11 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm">
                    <UploadCloud className="size-5" />
                </span>
                <span className="text-sm font-medium text-slate-800">
                    <span className="text-brand-600">Pilih file</span> atau
                    seret ke sini
                </span>
                <span className="mt-1 text-xs text-slate-500">
                    {hint ??
                        `Maksimum ${formatFileSize(maxSizeKb * 1024)} per file`}
                </span>
                <input
                    ref={inputRef}
                    id={inputId}
                    type="file"
                    className="sr-only"
                    accept={accept?.join(',')}
                    multiple={multiple}
                    disabled={disabled}
                    onChange={(event) => {
                        addFiles(event.target.files);
                        event.target.value = '';
                    }}
                />
            </label>

            {message && <p className="text-sm text-rose-600">{message}</p>}

            {value.length > 0 && (
                <ul className="space-y-2">
                    {value.map((file, index) => (
                        <Preview
                            key={`${file.name}-${file.lastModified}-${index}`}
                            file={file}
                            onRemove={() =>
                                onChange(value.filter((_, i) => i !== index))
                            }
                        />
                    ))}
                </ul>
            )}
        </div>
    );
}
