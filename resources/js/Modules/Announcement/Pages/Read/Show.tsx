import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, FileText, Pencil, Pin } from 'lucide-react';
import { useState } from 'react';
import { Lightbox } from '@/Components/shared/Lightbox';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import { Button } from '@/Components/ui/button';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate, formatFileSize } from '@/lib/format';
import type { AnnouncementRow } from '@/Modules/Announcement/types';

type Props = {
    base: string;
    announcement: AnnouncementRow;
    canManage: boolean;
};

export default function ShowAnnouncement({
    base,
    announcement,
    canManage,
}: Props) {
    const { auth } = usePage().props;
    const [index, setIndex] = useState<number | null>(null);
    const images = announcement.attachments.filter((a) => a.is_image);
    const documents = announcement.attachments.filter((a) => !a.is_image);

    const article = (
        <article className="max-w-3xl space-y-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <Head title={announcement.title} />
            <div className="space-y-1">
                {announcement.pinned && (
                    <p className="flex items-center gap-1 text-xs font-medium text-brand-600">
                        <Pin className="size-3.5" />
                        Disematkan
                    </p>
                )}
                <h1 className="text-xl font-semibold break-words text-slate-900">
                    {announcement.title}
                </h1>
                <p className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    {announcement.publish_at
                        ? formatDate(
                              announcement.publish_at,
                              'EEEE, d MMMM yyyy, HH.mm',
                          )
                        : 'Belum terbit'}
                    {announcement.creator && ` · ${announcement.creator}`}
                    {canManage && (
                        <StatusBadge
                            variant={
                                announcement.status.value === 'TERBIT'
                                    ? 'success'
                                    : announcement.status.value === 'TERJADWAL'
                                      ? 'info'
                                      : 'neutral'
                            }
                        >
                            {announcement.status.label} ·{' '}
                            {announcement.audience.label}
                        </StatusBadge>
                    )}
                </p>
            </div>

            <div className="text-sm leading-relaxed break-words whitespace-pre-wrap text-slate-700">
                {announcement.body}
            </div>

            {images.length > 0 && (
                <ul className="grid grid-cols-2 gap-2 sm:grid-cols-3">
                    {images.map((image, i) => (
                        <li key={image.public_id}>
                            <button
                                type="button"
                                onClick={() => setIndex(i)}
                                className="block w-full overflow-hidden rounded-xl border border-slate-200"
                                aria-label={`Lihat ${image.name}`}
                            >
                                <img
                                    src={image.thumb_url}
                                    alt={image.name}
                                    loading="lazy"
                                    className="aspect-video w-full object-cover"
                                />
                            </button>
                        </li>
                    ))}
                </ul>
            )}

            {documents.length > 0 && (
                <ul className="space-y-2">
                    {documents.map((doc) => (
                        <li key={doc.public_id}>
                            <a
                                href={doc.url}
                                target="_blank"
                                rel="noreferrer"
                                className="flex items-center gap-3 rounded-xl border border-slate-200 p-3 text-sm hover:bg-slate-50"
                            >
                                <FileText className="size-6 shrink-0 text-brand-500" />
                                <span className="min-w-0 flex-1">
                                    <span className="block truncate font-medium text-slate-800">
                                        {doc.name}
                                    </span>
                                    <span className="text-xs text-slate-500">
                                        {formatFileSize(doc.size)}
                                    </span>
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>
            )}

            <Lightbox
                images={images.map((i) => ({ url: i.url, name: i.name }))}
                index={index}
                onIndexChange={setIndex}
                title={announcement.title}
            />
        </article>
    );

    if (auth.activeRole === 'guardian') {
        return (
            <ParentLayout>
                <Button variant="ghost" size="sm" className="-ml-2" asChild>
                    <Link href={base}>
                        <ArrowLeft />
                        Semua pengumuman
                    </Link>
                </Button>
                {article}
            </ParentLayout>
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Pengumuman', href: base },
                { title: announcement.title, href: '#' },
            ]}
        >
            <PageHeader
                title="Pengumuman"
                actions={
                    canManage && (
                        <Button variant="outline" asChild>
                            <Link
                                href={`${base}/${announcement.public_id}/ubah`}
                            >
                                <Pencil />
                                Ubah
                            </Link>
                        </Button>
                    )
                }
            />
            {article}
        </AppLayout>
    );
}
