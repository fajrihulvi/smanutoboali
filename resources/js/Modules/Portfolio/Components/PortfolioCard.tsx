import { Link } from '@inertiajs/react';
import { FileText, Images, Pencil, Users } from 'lucide-react';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { formatDate } from '@/lib/format';
import type { PortfolioRow } from '@/Modules/Portfolio/types';

type Props = {
    portfolio: PortfolioRow;
    onOpenImage: (index: number) => void;
    editHref?: string;
    showVisibility?: boolean;
};

/**
 * Kartu galeri: gambar sampul (klik → lightbox), judul, kategori, tanggal, siswa, dan lampiran PDF.
 */
export function PortfolioCard({
    portfolio,
    onOpenImage,
    editHref,
    showVisibility = false,
}: Props) {
    const images = portfolio.files.filter((file) => file.is_image);
    const documents = portfolio.files.filter((file) => !file.is_image);
    const cover = images[0];

    return (
        <article className="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            {cover ? (
                <button
                    type="button"
                    onClick={() => onOpenImage(0)}
                    className="group relative aspect-[4/3] overflow-hidden bg-slate-100 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none"
                    aria-label={`Lihat foto ${portfolio.title}`}
                >
                    <img
                        src={cover.thumb_url}
                        alt={portfolio.title}
                        loading="lazy"
                        className="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    {images.length > 1 && (
                        <span className="absolute right-2 bottom-2 flex items-center gap-1 rounded-full bg-black/60 px-2 py-0.5 text-xs text-white">
                            <Images className="size-3" /> {images.length}
                        </span>
                    )}
                </button>
            ) : (
                <div className="flex aspect-[4/3] items-center justify-center bg-brand-50 text-brand-500">
                    <FileText className="size-10" />
                </div>
            )}
            <div className="flex flex-1 flex-col gap-2 p-4">
                <div className="flex flex-wrap items-center gap-1.5">
                    <StatusBadge variant="brand">
                        {portfolio.category.name}
                    </StatusBadge>
                    {showVisibility && (
                        <StatusBadge
                            variant={
                                portfolio.visibility.value === 'PUBLISHED'
                                    ? 'success'
                                    : 'neutral'
                            }
                            dot
                        >
                            {portfolio.visibility.label}
                        </StatusBadge>
                    )}
                </div>
                <h3 className="font-semibold text-slate-900">
                    {portfolio.title}
                </h3>
                <p className="text-xs text-slate-500">
                    {formatDate(portfolio.date, 'd MMMM yyyy')}
                </p>
                {portfolio.description && (
                    <p className="line-clamp-3 text-sm text-slate-600">
                        {portfolio.description}
                    </p>
                )}
                {documents.length > 0 && (
                    <ul className="space-y-1">
                        {documents.map((file) => (
                            <li key={file.public_id}>
                                <a
                                    href={file.url}
                                    target="_blank"
                                    rel="noopener"
                                    className="flex items-center gap-1.5 text-sm text-brand-700 hover:underline"
                                >
                                    <FileText className="size-4 shrink-0" />
                                    <span className="truncate">
                                        {file.name}
                                    </span>
                                </a>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="mt-auto flex items-center justify-between gap-2 pt-2 text-xs text-slate-500">
                    <span className="flex min-w-0 items-center gap-1">
                        <Users className="size-3.5 shrink-0" />
                        <span className="truncate">
                            {portfolio.students.map((s) => s.name).join(', ')}
                            {portfolio.students_count >
                                portfolio.students.length &&
                                portfolio.students.length > 0 &&
                                ' & teman'}
                        </span>
                    </span>
                    {editHref && portfolio.can.update && (
                        <Link
                            href={editHref}
                            className="flex shrink-0 items-center gap-1 font-medium text-brand-700 hover:underline"
                        >
                            <Pencil className="size-3.5" /> Ubah
                        </Link>
                    )}
                </div>
            </div>
        </article>
    );
}
