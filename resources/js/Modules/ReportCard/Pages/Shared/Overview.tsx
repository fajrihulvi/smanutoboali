import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Shapes } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import AppLayout from '@/Layouts/AppLayout';
import type { Option } from '@/Modules/Academic/types';
import { SemesterSelect } from '@/Modules/Assessment/Components/SemesterSelect';
import { StatusCounts } from '@/Modules/ReportCard/Components/StatusCounts';
import type { StatusCounts as Counts } from '@/Modules/ReportCard/types';

type Props = {
    base: string;
    semester: string | null;
    semesters: Option[];
    classrooms: {
        public_id: string;
        name: string;
        homeroom: string | null;
        total: number;
        counts: Counts;
    }[];
};

export default function ReportCardOverview({
    base,
    semester,
    semesters,
    classrooms,
}: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Raport', href: base }]}>
            <Head title="Raport" />

            <PageHeader
                title="Raport"
                description="Status raport per rombel. Buka rombel untuk memproses tahap berikutnya."
                actions={
                    semesters.length > 0 && (
                        <SemesterSelect
                            semesters={semesters}
                            value={semester}
                        />
                    )
                }
            />

            {classrooms.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={Shapes}
                        title="Belum ada rombel tahun ajaran aktif"
                    />
                </div>
            ) : (
                <ul className="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    {classrooms.map((classroom) => (
                        <li key={classroom.public_id}>
                            <Link
                                href={`${base}/rombel/${classroom.public_id}?semester=${semester ?? ''}`}
                                className="flex flex-wrap items-center gap-3 px-5 py-4 hover:bg-slate-50"
                            >
                                <div className="w-40 min-w-0">
                                    <p className="font-semibold text-slate-900">
                                        {classroom.name}
                                    </p>
                                    <p className="truncate text-xs text-slate-500">
                                        Wali: {classroom.homeroom ?? '—'}
                                    </p>
                                </div>
                                <div className="min-w-0 flex-1">
                                    <StatusCounts counts={classroom.counts} />
                                </div>
                                <span className="text-sm text-slate-500 tabular-nums">
                                    {classroom.total} raport
                                </span>
                                <ChevronRight className="size-4 text-slate-400" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
