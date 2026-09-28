import { Head, usePage } from '@inertiajs/react';
import { Megaphone } from 'lucide-react';
import type { ReactNode } from 'react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { Pagination } from '@/Components/shared/Pagination';
import AppLayout from '@/Layouts/AppLayout';
import ParentLayout from '@/Layouts/ParentLayout';
import { AnnouncementCard } from '@/Modules/Announcement/Components/AnnouncementCard';
import type { AnnouncementRow } from '@/Modules/Announcement/types';
import type { Paginated } from '@/types';

type Props = {
    base: string;
    announcements: Paginated<AnnouncementRow>;
};

export default function ReadAnnouncements({ base, announcements }: Props) {
    const { auth } = usePage().props;

    const content = (
        <>
            <Head title="Pengumuman" />
            {announcements.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Megaphone}
                        title="Belum ada pengumuman"
                        description="Pengumuman dari sekolah akan tampil di sini."
                    />
                </div>
            ) : (
                <div className="space-y-3">
                    {announcements.data.map((announcement) => (
                        <AnnouncementCard
                            key={announcement.public_id}
                            href={`${base}/${announcement.public_id}`}
                            announcement={announcement}
                        />
                    ))}
                    <Pagination paginated={announcements} />
                </div>
            )}
        </>
    );

    return auth.activeRole === 'guardian' ? (
        <ParentLayout>
            <Title />
            {content}
        </ParentLayout>
    ) : (
        <AppLayout breadcrumbs={[{ title: 'Pengumuman', href: base }]}>
            <PageHeader
                title="Pengumuman"
                description="Pengumuman resmi dari sekolah untuk Anda."
            />
            <div className="max-w-3xl">{content}</div>
        </AppLayout>
    );
}

function Title(): ReactNode {
    return (
        <div>
            <h1 className="text-xl font-semibold text-slate-900">Pengumuman</h1>
            <p className="text-sm text-slate-500">
                Informasi resmi dari sekolah.
            </p>
        </div>
    );
}
