import { Download, ExternalLink, FileText, Link2 } from 'lucide-react';
import type { ReactNode } from 'react';
import { formatDate, formatFileSize } from '@/lib/format';
import type { MaterialRow } from '@/Modules/Academic/types';

type Props = {
    material: MaterialRow;
    /** Tampilkan mapel & rombel (daftar lintas mapel). */
    showContext?: boolean;
    actions?: ReactNode;
};

/**
 * Satu materi: ikon jenis, judul, deskripsi, dan tombol buka tautan / unduh file.
 */
export function MaterialItem({
    material,
    showContext = false,
    actions,
}: Props) {
    const isLink = material.type.value === 'LINK';
    const Icon = isLink ? Link2 : FileText;
    const href = isLink ? material.url : material.file?.url;

    return (
        <div className="flex gap-3">
            <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                <Icon className="size-5" />
            </span>
            <div className="min-w-0 flex-1">
                <p className="font-medium break-words text-slate-900">
                    {material.title}
                </p>
                <p className="text-xs text-slate-500">
                    {showContext &&
                        `${material.subject} · ${material.classroom} · `}
                    {material.created_at && formatDate(material.created_at)}
                    {material.file &&
                        ` · ${formatFileSize(material.file.size)}`}
                </p>
                {material.description && (
                    <p className="mt-1 text-sm whitespace-pre-line text-slate-600">
                        {material.description}
                    </p>
                )}
                {href && (
                    <a
                        href={href}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-brand-700 hover:underline"
                    >
                        {isLink ? (
                            <ExternalLink className="size-4" />
                        ) : (
                            <Download className="size-4" />
                        )}
                        {isLink
                            ? 'Buka tautan'
                            : (material.file?.name ?? 'Unduh file')}
                    </a>
                )}
            </div>
            {actions && <div className="shrink-0">{actions}</div>}
        </div>
    );
}
