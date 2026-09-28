import { Head, Link, router } from '@inertiajs/react';
import { BookOpen, ChevronRight } from 'lucide-react';
import { EmptyState } from '@/Components/shared/EmptyState';
import { PageHeader } from '@/Components/shared/PageHeader';
import { StatusBadge } from '@/Components/shared/StatusBadge';
import AppLayout from '@/Layouts/AppLayout';
import { NativeSelect } from '@/Modules/Academic/Components/NativeSelect';
import type { Option } from '@/Modules/Academic/types';
import { SemesterSelect } from '@/Modules/Assessment/Components/SemesterSelect';

type Props = {
    semester: string | null;
    semesters: Option[];
    classrooms: Option[];
    classroom: string | null;
    assignments: {
        public_id: string;
        subject: string;
        teacher: string;
        components: number;
        total_weight: number;
    }[];
};

const BASE = '/operator/nilai';

export default function OperatorGradesIndex({
    semester,
    semesters,
    classrooms,
    classroom,
    assignments,
}: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Nilai', href: BASE }]}>
            <Head title="Nilai" />

            <PageHeader
                title="Nilai"
                description="Lihat buku nilai tiap mapel (hanya baca). Nilai diisi oleh guru pengampu."
            />

            <div className="mb-4 flex flex-wrap gap-2">
                <SemesterSelect
                    semesters={semesters}
                    value={semester}
                    query={{ rombel: classroom ?? undefined }}
                />
                <NativeSelect
                    aria-label="Rombel"
                    className="w-40"
                    value={classroom ?? ''}
                    onChange={(event) =>
                        router.get(BASE, {
                            semester: semester ?? undefined,
                            rombel: event.target.value,
                        })
                    }
                >
                    {classrooms.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </NativeSelect>
            </div>

            {assignments.length === 0 ? (
                <div className="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <EmptyState
                        icon={BookOpen}
                        title="Belum ada penugasan di rombel ini"
                    />
                </div>
            ) : (
                <ul className="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    {assignments.map((row) => (
                        <li key={row.public_id}>
                            <Link
                                href={`${BASE}/${row.public_id}?semester=${semester ?? ''}`}
                                className="flex items-center gap-3 px-5 py-3 hover:bg-slate-50"
                            >
                                <div className="min-w-0 flex-1">
                                    <p className="font-medium text-slate-900">
                                        {row.subject}
                                    </p>
                                    <p className="text-xs text-slate-500">
                                        {row.teacher}
                                    </p>
                                </div>
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
                                <ChevronRight className="size-4 text-slate-400" />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </AppLayout>
    );
}
