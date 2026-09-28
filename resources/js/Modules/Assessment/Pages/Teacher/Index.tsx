import { Head, Link } from '@inertiajs/react';
import { BookOpen, ChevronRight, Lock, UserRoundX } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import AppLayout from '@/Layouts/AppLayout';
import type { Option } from '@/Modules/Academic/types';
import { SemesterSelect } from '@/Modules/Assessment/Components/SemesterSelect';

type Props = {
    hasTeacherProfile: boolean;
    semester: string | null;
    semesters: Option[];
    assignments: {
        public_id: string;
        subject: string;
        classroom: string;
        components: number;
        total_weight: number;
        published: number;
    }[];
};

const BASE = '/guru/akademik/nilai';

export default function TeacherGradesIndex({
    hasTeacherProfile,
    semester,
    semesters,
    assignments,
}: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Nilai', href: BASE }]}>
            <Head title="Nilai" />

            <PageHeader
                title="Nilai"
                description="Pilih mapel & rombel untuk mengisi nilai."
                actions={
                    semesters.length > 0 && (
                        <SemesterSelect
                            semesters={semesters}
                            value={semester}
                        />
                    )
                }
            />

            {!hasTeacherProfile ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={UserRoundX}
                        title="Akun belum terhubung ke data guru"
                    />
                </div>
            ) : assignments.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={BookOpen}
                        title="Belum ada mapel yang diampu"
                        description="Anda belum punya penugasan mengajar di tahun ajaran aktif."
                    />
                </div>
            ) : (
                <ul className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    {assignments.map((row) => (
                        <li key={row.public_id}>
                            <Link
                                href={`${BASE}/${row.public_id}?semester=${semester ?? ''}`}
                                className="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-colors duration-150 hover:border-brand-300 focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:outline-none"
                            >
                                <p className="text-sm text-slate-500">
                                    {row.classroom}
                                </p>
                                <p className="text-lg font-semibold text-slate-900">
                                    {row.subject}
                                </p>
                                <div className="mt-3 flex flex-wrap gap-1.5">
                                    {row.components === 0 ? (
                                        <StatusBadge variant="neutral">
                                            Belum ada komponen
                                        </StatusBadge>
                                    ) : (
                                        <StatusBadge
                                            variant={
                                                row.total_weight === 100
                                                    ? 'success'
                                                    : 'warning'
                                            }
                                        >
                                            {row.components} komponen ·{' '}
                                            {row.total_weight}%
                                        </StatusBadge>
                                    )}
                                    {row.published > 0 && (
                                        <StatusBadge variant="brand">
                                            <Lock className="size-3" />{' '}
                                            {row.published} dipublikasikan
                                        </StatusBadge>
                                    )}
                                </div>
                                <p className="mt-auto flex items-center gap-1 pt-4 text-sm font-medium text-brand-700">
                                    Buka buku nilai
                                    <ChevronRight className="size-4 transition-transform duration-150 group-hover:translate-x-0.5" />
                                </p>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
