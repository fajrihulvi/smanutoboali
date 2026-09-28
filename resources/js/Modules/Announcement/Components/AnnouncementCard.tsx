import { Link } from '@inertiajs/react';
import { Paperclip, Pin } from 'lucide-react';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { AnnouncementRow } from '@/Modules/Announcement/types';

/**
 * Kartu pengumuman untuk pembaca: pin, judul, cuplikan, tanggal, jumlah lampiran.
 */
export function AnnouncementCard({
    href,
    announcement,
}: {
    href: string;
    announcement: AnnouncementRow;
}) {
    return (
        <Link
            href={href}
            className={cn(
                'block rounded-2xl border bg-white p-4 shadow-sm transition-colors duration-150 hover:border-brand-300 sm:p-5',
                announcement.pinned ? 'border-brand-200' : 'border-slate-200',
            )}
        >
            {announcement.pinned && (
                <p className="mb-1 flex items-center gap-1 text-xs font-medium text-brand-600">
                    <Pin className="size-3.5" />
                    Disematkan
                </p>
            )}
            <h2 className="font-semibold text-slate-900">
                {announcement.title}
            </h2>
            <p className="mt-1 line-clamp-3 text-sm text-slate-600">
                {announcement.body}
            </p>
            <p className="mt-2 flex flex-wrap items-center gap-x-2 text-xs text-slate-400">
                <span>
                    {formatDate(announcement.publish_at, 'd MMM yyyy, HH.mm')}
                </span>
                {announcement.creator && <span>· {announcement.creator}</span>}
                {announcement.attachments.length > 0 && (
                    <span className="flex items-center gap-0.5">
                        · <Paperclip className="size-3" />
                        {announcement.attachments.length} lampiran
                    </span>
                )}
            </p>
        </Link>
    );
}
