import { Head, Link } from '@inertiajs/react';
import { Baby, CalendarDays, PartyPopper } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { Pagination } from '@/Components/shared/Pagination';
import type { SwitcherChild } from '@/Components/shared/StudentSwitcher';
import { Button } from '@/Components/ui/button';
import ParentLayout from '@/Layouts/ParentLayout';
import { formatDate } from '@/lib/format';
import { ActivityMeta } from '@/Modules/Activity/Components/ActivityMeta';
import { PhotoStrip } from '@/Modules/Activity/Components/PhotoStrip';
import type { ActivityRow } from '@/Modules/Activity/types';
import type { Paginated } from '@/types';

type Props = {
    childOptions: SwitcherChild[];
    activeChild: string | null;
    child: { public_id: string; name: string } | null;
    activities: Paginated<ActivityRow> | null;
};

export default function GuardianActivityFeed({ child, activities }: Props) {
    return (
        <ParentLayout>
            <Head title="Kegiatan" />

            <div className="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold text-slate-900">
                        Kegiatan
                    </h1>
                    <p className="text-sm text-slate-500">
                        {child
                            ? `Kegiatan sekolah & kelas ${child.name}`
                            : 'Kegiatan anak di sekolah.'}
                    </p>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <Link href="/orang-tua/kegiatan/kalender">
                        <CalendarDays />
                        Kalender
                    </Link>
                </Button>
            </div>

            {!child ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState icon={Baby} title="Belum ada anak terhubung" />
                </div>
            ) : !activities || activities.data.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={PartyPopper}
                        title="Belum ada kegiatan"
                        description="Kegiatan yang diterbitkan sekolah akan tampil di sini."
                    />
                </div>
            ) : (
                <>
                    <ol className="relative space-y-6 border-l-2 border-brand-100 pl-5">
                        {activities.data.map((activity) => (
                            <li key={activity.public_id} className="relative">
                                <span
                                    className="absolute top-1.5 -left-[27px] size-3 rounded-full border-2 border-white bg-brand-500 ring-2 ring-brand-100"
                                    aria-hidden="true"
                                />
                                <p className="mb-2 text-xs font-semibold tracking-wide text-brand-700 uppercase">
                                    {formatDate(
                                        activity.date,
                                        'EEEE, d MMMM yyyy',
                                    )}
                                </p>
                                <article className="space-y-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <h2 className="font-semibold text-slate-900">
                                        {activity.title}
                                    </h2>
                                    <ActivityMeta
                                        activity={activity}
                                        showDate={false}
                                    />
                                    {activity.description && (
                                        <p className="text-sm whitespace-pre-line text-slate-700">
                                            {activity.description}
                                        </p>
                                    )}
                                    <PhotoStrip activity={activity} />
                                </article>
                            </li>
                        ))}
                    </ol>
                    {activities.meta.last_page > 1 && (
                        <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <Pagination
                                paginated={activities}
                                only={['activities']}
                            />
                        </div>
                    )}
                </>
            )}
        </ParentLayout>
    );
}
